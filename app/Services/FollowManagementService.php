<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Brand;
use App\Models\Follow;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The dashboard FOLLOWING / FOLLOWERS management surface (Prompt 5-follow):
 * an actor's own follow graph, shaped for pruning — not the public listing.
 *
 * Both sides page through the follows table (24/page); the polymorphic
 * `followable`/`follower` relation is eager-loaded with MorphTo::morphWith so
 * talents arrive with their Skills + media and brands with their logo (a
 * plain with() would N+1 across the mixed types). Mutuality is resolved in
 * ONE bulk FollowService query for the whole page, never per row.
 *
 * The dashboard-only signals (mutual badge, quiet-since) live HERE and in the
 * dashboard resource — never in the public FollowListController payload.
 */
class FollowManagementService extends Service
{
    protected string $logChannel = 'feed';

    /** Accounts silent this many days show the "quiet since" prune signal. */
    private const QUIET_DAYS = 90;

    public function __construct(private readonly FollowService $follows)
    {
    }

    /**
     * The actor's FOLLOWING page — the accounts THEY follow, each with the
     * mutual badge, quiet-since signal, and filters applied.
     *
     * @param  array{q?: string|null, type?: string|null, skill?: string|null, sort?: string|null}  $filters
     * @return array{rows: Collection<int, array<string, mixed>>, paginator: LengthAwarePaginator<int, Follow>}
     */
    public function following(Actor $actor, array $filters): array
    {
        return $this->page($actor, side: 'followable', filters: $filters);
    }

    /**
     * The actor's FOLLOWERS page — the accounts that follow THEM. Same card;
     * the control is FOLLOW BACK where the edge is not mutual, no unfollow.
     *
     * @param  array{q?: string|null, type?: string|null, skill?: string|null, sort?: string|null}  $filters
     * @return array{rows: Collection<int, array<string, mixed>>, paginator: LengthAwarePaginator<int, Follow>}
     */
    public function followers(Actor $actor, array $filters): array
    {
        return $this->page($actor, side: 'follower', filters: $filters);
    }

    /**
     * Build one page of the follow graph on the given side, hydrated and
     * shaped. `side` is the RELATION on the Follow row to read (the OTHER
     * party): 'followable' for FOLLOWING, 'follower' for FOLLOWERS.
     *
     * @param  array{q?: string|null, type?: string|null, skill?: string|null, sort?: string|null}  $filters
     * @return array{rows: Collection<int, array<string, mixed>>, paginator: LengthAwarePaginator<int, Follow>}
     */
    private function page(Actor $actor, string $side, array $filters): array
    {
        $ownIsFollowing = $side === 'followable';
        $meColumn = $ownIsFollowing ? 'follower' : 'followable';
        $otherColumn = $ownIsFollowing ? 'followable' : 'follower';

        $sort = $filters['sort'] ?? 'recent';
        $type = $this->normaliseType($filters['type'] ?? null);
        $q = trim((string) ($filters['q'] ?? ''));
        $skill = trim((string) ($filters['skill'] ?? ''));

        $query = Follow::query()
            ->where("{$meColumn}_type", $actor->getMorphClass())
            ->where("{$meColumn}_id", $actor->getKey())
            ->with([$otherColumn => fn (MorphTo $morph) => $morph->morphWith([
                // `projects` = the three latest, for the row's work strip (per-parent
                // limit, one query + one media query for the whole page).
                Talent::class => ['talentTypes', 'media', 'projects' => fn ($projects) => $projects
                    ->reorder()->orderByDesc('occurred_on')->orderByDesc('id')->limit(3)->with('media')],
                Brand::class => ['media'],
            ])]);

        // Type filter narrows the polymorphic other-party by morph alias.
        if ($type !== null) {
            $query->where("{$otherColumn}_type", $type);
        }

        // Search + alphabetical/least-active sorts need the other party's own
        // columns, so left-join both target tables on the morph column (each
        // follow matches at most one, constrained by type). Done in SQL so
        // pagination stays correct — never an in-memory filter of a fetched
        // page, which would return short/empty pages when matches sit later.
        if ($q !== '' || $sort === 'alpha' || $sort === 'least_active') {
            $this->joinTargets($query, $otherColumn);
        }

        if ($q !== '') {
            $this->applySearch($query, $q);
        }

        // Skill filter: talents tagged with the slug (brands can't match).
        if ($skill !== '') {
            $this->applySkill($query, $otherColumn, $skill);
        }

        $this->applySort($query, $sort);

        /** @var LengthAwarePaginator<int, Follow> $paginator */
        $paginator = $query->paginate(24)->withQueryString();

        $actors = $paginator->getCollection()
            ->map(fn (Follow $follow) => $follow->getRelation($otherColumn))
            ->filter(fn ($other) => $other instanceof Actor)
            ->values();

        // Mutuality in one bulk query for the whole page (never per row).
        $mutual = $ownIsFollowing
            ? $this->follows->followsBack($actor, $actors)
            : $this->follows->isFollowing($actor, $actors);

        $rows = $paginator->getCollection()
            ->map(fn (Follow $follow) => $this->card($follow, $otherColumn, $ownIsFollowing, $mutual))
            ->filter()
            ->values();

        return ['rows' => $rows, 'paginator' => $paginator];
    }

    /**
     * Left-join both possible target tables on the morph column, keeping the
     * Follow model selected. Each row joins at most one table (the join is
     * type-constrained), so COALESCE across them reads the right party.
     */
    private function joinTargets($query, string $otherColumn): void
    {
        $query
            ->leftJoin('talents', function ($join) use ($otherColumn): void {
                $join->on("follows.{$otherColumn}_id", '=', 'talents.id')
                    ->where("follows.{$otherColumn}_type", '=', 'talent');
            })
            ->leftJoin('brands', function ($join) use ($otherColumn): void {
                $join->on("follows.{$otherColumn}_id", '=', 'brands.id')
                    ->where("follows.{$otherColumn}_type", '=', 'brand');
            })
            ->select('follows.*');
    }

    /**
     * Search display name + Username across both target types (Username is the
     * `slug` column on each; display name is talents.display_name /
     * brands.name).
     */
    private function applySearch($query, string $q): void
    {
        $like = '%'.mb_strtolower($q).'%';

        $query->where(function ($inner) use ($like): void {
            $inner
                ->whereRaw('LOWER(COALESCE(talents.display_name, brands.name)) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(talents.slug, brands.slug)) LIKE ?', [$like]);
        });
    }

    /**
     * Constrain to talents tagged with the given Skill slug (a Skill filter
     * excludes brands, which have no Skills). whereExists over the pivot keeps
     * it a single query.
     */
    private function applySkill($query, string $otherColumn, string $skill): void
    {
        $query
            ->where("follows.{$otherColumn}_type", 'talent')
            ->whereExists(function ($sub) use ($otherColumn, $skill): void {
                $sub->selectRaw('1')
                    ->from('talent_talent_type')
                    ->join('talent_types', 'talent_types.id', '=', 'talent_talent_type.talent_type_id')
                    ->whereColumn('talent_talent_type.talent_id', "follows.{$otherColumn}_id")
                    ->where('talent_types.slug', $skill);
            });
    }

    /**
     * The Skills actually present among the accounts on this side — the Skill
     * filter is populated from these, not the full catalog, so it never offers
     * a Skill that would return nothing. One query.
     *
     * @return array<int, array{slug: string, name: mixed}>
     */
    public function presentSkills(Actor $actor, string $side): array
    {
        $ownIsFollowing = $side === 'followable';
        $meColumn = $ownIsFollowing ? 'follower' : 'followable';
        $otherColumn = $ownIsFollowing ? 'followable' : 'follower';

        return \App\Models\TalentType::query()
            ->whereExists(function ($sub) use ($actor, $meColumn, $otherColumn): void {
                $sub->selectRaw('1')
                    ->from('follows')
                    ->join('talent_talent_type', function ($join) use ($otherColumn): void {
                        $join->on('talent_talent_type.talent_id', '=', "follows.{$otherColumn}_id")
                            ->where("follows.{$otherColumn}_type", '=', 'talent');
                    })
                    ->whereColumn('talent_talent_type.talent_type_id', 'talent_types.id')
                    ->where("follows.{$meColumn}_type", $actor->getMorphClass())
                    ->where("follows.{$meColumn}_id", $actor->getKey());
            })
            ->orderBy('id')
            ->get(['slug', 'name'])
            ->map(fn ($skill) => ['slug' => $skill->slug, 'name' => $skill->name])
            ->all();
    }

    /**
     * Order the page: recently-followed (default), alphabetical by display
     * name, or least-active (prune candidates first — talents by
     * last_posted_at ASC with NULLs, i.e. never-posted, surfaced first;
     * brands have no post signal and sort last). Alphabetical + least-active
     * join the other party's table per morph type.
     */
    private function applySort($query, string $sort): void
    {
        if ($sort === 'alpha') {
            $query->orderByRaw('COALESCE(talents.display_name, brands.name) ASC');

            return;
        }

        if ($sort === 'least_active') {
            // Quiet longest first: a NULL last_posted_at (never posted, or a
            // brand — no post signal) is the quietest, so surface it first,
            // then oldest post ascending; ties break on most-recently-followed.
            $query
                ->orderByRaw('talents.last_posted_at IS NULL DESC')
                ->orderByRaw('talents.last_posted_at ASC')
                ->orderBy('follows.created_at', 'desc');

            return;
        }

        // Recently followed (default).
        $query->latest('follows.created_at');
    }

    /**
     * Shape one follow edge into a management card. The other party is a
     * hydrated Talent or Brand; talents carry Skills + last_posted_at, brands
     * carry sector + city.
     *
     * @param  array<string, bool>  $mutual
     * @return array<string, mixed>|null
     */
    private function card(Follow $follow, string $otherColumn, bool $ownIsFollowing, array $mutual): ?array
    {
        $other = $follow->getRelation($otherColumn);

        if (! $other instanceof Actor) {
            return null;
        }

        $key = $other->getMorphClass().':'.$other->getKey();

        $base = [
            'follow_id' => $follow->getKey(),
            'type' => $other->getMorphClass(),
            'id' => $other->getKey(),
            'username' => $other->feedUsername(),
            'display_name' => $other->feedDisplayName(),
            'avatar_url' => $other->feedAvatarUrl(),
            'followed_at' => $follow->created_at?->isoFormat('DD MMM YYYY'),
            'mutual' => $mutual[$key] ?? false,
            // FOLLOWING: the control is Unfollow. FOLLOWERS: FOLLOW BACK when
            // the edge is not already mutual (no unfollow on that side).
            'can_unfollow' => $ownIsFollowing,
            'can_follow_back' => ! $ownIsFollowing && ! ($mutual[$key] ?? false),
        ];

        if ($other instanceof Talent) {
            return $base + [
                'skills' => $other->talentTypes
                    ->take(2)
                    ->map(fn ($skill) => ['name' => $skill->name, 'slug' => $skill->slug])
                    ->values()
                    ->all(),
                'quiet_since' => $this->quietSince($other->last_posted_at),
                // fama-ui Activity status line ("Posted 3 days ago") — the
                // column is already on the hydrated row, no extra query.
                'last_posted_at' => $other->last_posted_at?->toIso8601String(),
                // fama-ui Activity row: their three latest pieces of work (project
                // lead images). Projects without a picture are skipped — the strip
                // only renders when there is something to show.
                'work' => $this->workStrip($other),
            ];
        }

        // Brand.
        return $base + [
            'sector' => $other->industry,
            'city' => $other->base_city,
            'skills' => [],
            'quiet_since' => null,
            'last_posted_at' => null,
        ];
    }

    /**
     * Up to three lead-image URLs from the talent's latest projects (eager-
     * loaded in page() — never queried per row). Empty while the profile is
     * not public, so a private portfolio never shows through a follow row.
     *
     * @return list<string>
     */
    private function workStrip(Talent $talent): array
    {
        if (! $talent->is_published || ! $talent->relationLoaded('projects')) {
            return [];
        }

        return $talent->projects
            ->map(fn ($project) => $project->lead_image_url)
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * The "QUIET SINCE MARCH" signal: the month a talent last posted, when
     * that was over QUIET_DAYS ago (or "never" when they have never posted).
     * Null when the account is active — the card shows nothing.
     */
    private function quietSince(?Carbon $lastPostedAt): ?string
    {
        if ($lastPostedAt === null) {
            return __('Never posted');
        }

        if ($lastPostedAt->gt(now()->subDays(self::QUIET_DAYS))) {
            return null;
        }

        return __('Quiet since :month', ['month' => $lastPostedAt->isoFormat('MMMM')]);
    }

    /**
     * Normalise a type filter to a morph alias, or null for "all".
     */
    private function normaliseType(?string $type): ?string
    {
        return match ($type) {
            'talents', 'talent' => 'talent',
            'brands', 'brand' => 'brand',
            default => null,
        };
    }
}
