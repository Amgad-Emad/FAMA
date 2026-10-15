<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Recognition;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\CounterService;
use App\Services\ProjectCreditService;
use App\Services\SkillsService;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo data for EVERY fama-ui screen (2026-10-12), centred on the demo talent
 * (talent-demo@fama.test) so a single login shows each page full:
 *
 *   Profile structure — 120 days of daily views (chart ranges), five social links.
 *   Projects / editor / project page — covers, ordered galleries, Results rows with
 *     "Compared with", a pending + an off-platform + an accepted (co-owner) credit.
 *   Credits — credits WAITING for the demo talent's answer, recognitions incl. a pending one.
 *   Activity — saved posts, a mutual follow graph.
 *   Notifications + bell — one of every type, read and unread, today / this week / earlier.
 *   Gate states — a second, Private + unverified talent (talent-new@fama.test) mid-setup.
 *
 * Runs last in DatabaseSeeder and is IDEMPOTENT, so it also tops up an existing dev
 * DB: `php artisan db:seed --class=ScreensDemoSeeder`. Pair with `php artisan
 * demo:freshen` when the feed has aged past its ranking windows.
 */
class ScreensDemoSeeder extends Seeder
{
    use GeneratesCoverImages;

    /**
     * Seed every screen, then reconcile counters (DatabaseSeeder mutes observers).
     */
    public function run(): void
    {
        $demo = Talent::with('talentTypes')->where('email', 'talent-demo@fama.test')->first();

        if (! $demo) {
            return;
        }

        $peers = Talent::with('talentTypes')
            ->where('id', '!=', $demo->id)
            ->where('is_published', true)
            ->whereHas('talentTypes')
            ->orderBy('id')
            ->limit(12)
            ->get();

        if ($peers->count() < 6) {
            return;
        }

        // Wrap indexes so any pool of 6+ peers works (fresh seeds have 10–12).
        $peers = $peers->values();
        $peers = collect(range(0, 11))->map(fn (int $i) => $peers[$i % $peers->count()]);

        DB::transaction(function () use ($demo, $peers): void {
            $this->seedViews($demo);
            $this->seedSocials($demo);
            $this->seedProjects($demo, $peers);
            $this->seedCreditsWaiting($demo, $peers);
            $this->seedRecognitions($demo, $peers);
            $this->seedFollows($demo, $peers);
            $this->seedBookmarks($demo);
            $this->seedNotifications($demo, $peers);
        });

        $this->seedPrivateTalent();
        $this->usePrototypePhotos($demo->fresh());

        app(CounterService::class)->reconcile();
    }

    /**
     * 120 days of daily profile-view buckets with a gentle upward trend + weekly rhythm,
     * so the 7d / 30d / 90d / all ranges and the week-over-week delta all have data.
     */
    private function seedViews(Talent $demo): void
    {
        $rows = [];
        for ($d = 119; $d >= 0; $d--) {
            $day = now()->subDays($d);
            $trend = 4 + (119 - $d) * 0.12;
            $weekly = in_array($day->dayOfWeekIso, [6, 7], true) ? 0.6 : 1.0;
            $rows[] = [
                'talent_id' => $demo->id,
                'viewed_on' => $day->toDateString(),
                'views' => max(0, (int) round(($trend + (crc32($day->toDateString()) % 7)) * $weekly)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('talent_profile_views')->upsert($rows, ['talent_id', 'viewed_on'], ['views', 'updated_at']);
        $demo->forceFill(['view_count' => max((int) $demo->view_count, array_sum(array_column($rows, 'views')))])->save();
    }

    /**
     * Five social links (the cap is nine) so the About row and the editor list look real.
     */
    private function seedSocials(Talent $demo): void
    {
        $handle = $demo->slug;
        foreach ([
            ['platform' => 'instagram', 'handle' => $handle, 'url' => "https://instagram.com/{$handle}"],
            ['platform' => 'tiktok', 'handle' => $handle, 'url' => "https://tiktok.com/@{$handle}"],
            ['platform' => 'linkedin', 'handle' => $handle, 'url' => "https://linkedin.com/in/{$handle}"],
            ['platform' => 'behance', 'handle' => $handle, 'url' => "https://behance.net/{$handle}"],
            ['platform' => 'website', 'handle' => "{$handle}.studio", 'url' => "https://{$handle}.studio"],
        ] as $position => $row) {
            DB::table('talent_social_handles')->updateOrInsert(
                ['talent_id' => $demo->id, 'platform' => $row['platform']],
                $row + ['position' => $position, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    /**
     * Three rich projects on the demo talent (cover + ordered gallery + Results), each
     * carrying the credit kinds the Projects list and project page render: one pending
     * (awaiting confirmation), one off-platform (listed) and one accepted (a CO-OWNER).
     *
     * @param  Collection<int, Talent>  $peers
     */
    private function seedProjects(Talent $demo, Collection $peers): void
    {
        $skills = $demo->talentTypes->sortBy('pivot.position')->values();
        $specs = [
            ['Zeina Swim — SS27', 'Zeina Swim', 'Lead stylist', [['label' => 'Reach', 'value' => '1.2M', 'compare' => null], ['label' => 'Sell-through', 'value' => '68%', 'compare' => null], ['label' => 'Engagement', 'value' => '+40%', 'compare' => 'vs last season']]],
            ['Atlas Watches — Desert Hours', 'Atlas Watches', 'Stylist', [['label' => 'Views', 'value' => '860K', 'compare' => null], ['label' => 'Saves', 'value' => '+22%', 'compare' => 'vs brand average'], ['label' => 'Returns', 'value' => '-12%', 'compare' => 'vs last drop'], ['label' => 'Completion', 'value' => '91%', 'compare' => null]]],
            ['Nile Nights — editorial', '', 'Creative lead', []],
        ];

        foreach ($specs as $i => [$title, $client, $role, $results]) {
            $skill = $skills[$i % max(1, $skills->count())] ?? null;

            $project = Project::where('talent_id', $demo->id)->where('title->en', $title)->first();

            if (! $project) {
                $project = Project::factory()->for($demo)->create([
                    'title' => ['en' => $title],
                    'client_name' => $client ?: null,
                    'role' => $role,
                    'summary' => ['en' => '<p>A three-day shoot built around natural light, one location and a tight rail of looks.</p>'],
                    'body' => ['en' => '<p>We pulled references from the archive, cast locally and kept the edit to the frames that told the story.</p>'],
                    'results' => $results,
                    'occurred_on' => now()->subMonths(2 + $i * 3)->startOfMonth()->addDays(9),
                    'talent_type_id' => $skill?->id,
                ]);

                $project->addMedia($this->cover("screens-{$i}-cover", 1600, 1000))->toMediaCollection('cover');
                foreach (range(1, 4) as $n) {
                    $project->addMedia($this->cover("screens-{$i}-g{$n}", 1200, 1500))->toMediaCollection('media');
                }
            }

            $this->creditOn($project, $peers[$i * 3], 'pending', 'Photographer');
            $this->creditOn($project, $peers[$i * 3 + 1], $i === 0 ? 'accepted' : 'pending', 'Hair & makeup');

            ProjectCredit::firstOrCreate(
                ['project_id' => $project->id, 'talent_id' => null, 'name' => ['Salma Fathy', 'Youssef Adel', 'Mona Rizk'][$i]],
                ['talent_type_id' => $skill?->id ?? TalentType::value('id'), 'role' => 'Set designer', 'status' => 'listed', 'position' => 9],
            );
        }
    }

    /**
     * Credit `$peer` on `$project`: pending, or accepted through the SERVICE (accepting
     * must also guarantee a projects block on their profile — never write the column).
     */
    private function creditOn(Project $project, Talent $peer, string $state, string $role): void
    {
        $credit = ProjectCredit::firstOrCreate(
            ['project_id' => $project->id, 'talent_id' => $peer->id],
            ['talent_type_id' => $peer->talentTypes->first()->id, 'role' => $role, 'status' => 'pending',
                'position' => (int) $project->credits()->max('position') + 1],
        );

        if ($state === 'accepted' && (string) $credit->status === 'pending') {
            app(ProjectCreditService::class)->accept($credit);
            $credit->forceFill(['responded_at' => now()->subDays(6)])->save();
        }
    }

    /**
     * Two peers credit the demo talent on THEIR projects and wait for an answer —
     * the Credits page's "Waiting for you" card.
     *
     * @param  Collection<int, Talent>  $peers
     */
    private function seedCreditsWaiting(Talent $demo, Collection $peers): void
    {
        $skill = $demo->talentTypes->sortBy('pivot.position')->first();

        foreach ([$peers[9], $peers[10]] as $n => $owner) {
            $project = $owner->projects()->first() ?? Project::factory()->for($owner)->create([
                'title' => ['en' => ['Maison Nour — Resort', 'Cairo Fashion Week — backstage'][$n]],
                'talent_type_id' => $owner->talentTypes->first()->id,
            ]);

            ProjectCredit::firstOrCreate(
                ['project_id' => $project->id, 'talent_id' => $demo->id],
                ['talent_type_id' => $skill?->id ?? TalentType::value('id'), 'role' => ['Stylist', 'Assistant stylist'][$n],
                    'status' => 'pending', 'position' => 20 + $n, 'created_at' => now()->subDays($n + 1)],
            );
        }
    }

    /**
     * Six recognitions for the demo talent (shows 4 + "Show all") with one still pending.
     *
     * @param  Collection<int, Talent>  $peers
     */
    private function seedRecognitions(Talent $demo, Collection $peers): void
    {
        $lines = [
            ['Shot three campaigns together', 'Calm on set and honest about what a brief needs. The rail was always ready before we were.'],
            ['Assisted me for two years', 'Taught me to read a body before reading a size chart. Generous with everything she knows.'],
            ['We share a studio in Zamalek', 'The person everyone calls when a look is not working at 6am.'],
            ['Styled every editorial I directed last year', 'Brings references nobody else has and edits them down to the one that matters.'],
            ['Crewed together on a shoot in AlUla', 'Kept forty looks pressed in a desert wind. Enough said.'],
            ['Met on a Maison Nour shoot', 'Quick, precise and kind to the whole crew.'],
        ];

        foreach ($lines as $i => [$relationship, $body]) {
            $approved = $i !== 5;
            Recognition::updateOrCreate(
                ['talent_id' => $demo->id, 'author_talent_id' => $peers[$i]->id],
                ['relationship' => $relationship, 'body' => $body, 'status' => $approved ? 'approved' : 'pending',
                    'is_approved' => $approved, 'recognized_at' => now()->subDays(3 + $i * 9)],
            );
        }
    }

    /**
     * Following 10 / followers 8 with overlap, so Activity shows mutual "Follows you"
     * rows, Follow-back rows and plain ones.
     *
     * @param  Collection<int, Talent>  $peers
     */
    private function seedFollows(Talent $demo, Collection $peers): void
    {
        foreach ($peers->take(10) as $i => $peer) {
            Follow::firstOrCreate(
                ['follower_type' => 'talent', 'follower_id' => $demo->id, 'followable_type' => 'talent', 'followable_id' => $peer->id],
                ['created_at' => now()->subDays(2 + $i * 4)],
            );
        }

        foreach ($peers->slice(4, 8) as $i => $peer) {
            Follow::firstOrCreate(
                ['follower_type' => 'talent', 'follower_id' => $peer->id, 'followable_type' => 'talent', 'followable_id' => $demo->id],
                ['created_at' => now()->subDays(1 + $i * 3)],
            );
        }
    }

    /**
     * Six saved posts by other people — Activity › Saved.
     */
    private function seedBookmarks(Talent $demo): void
    {
        Post::published()
            ->where(fn ($q) => $q->where('author_type', '!=', 'talent')->orWhere('author_id', '!=', $demo->id))
            ->latest('published_at')
            ->limit(6)
            ->get()
            ->each(fn (Post $post, int $i) => Bookmark::firstOrCreate(
                ['bookmarker_type' => 'talent', 'bookmarker_id' => $demo->id, 'bookmarkable_type' => 'post', 'bookmarkable_id' => $post->id],
                ['created_at' => now()->subHours(3 + $i * 20)],
            ));
    }

    /**
     * One notification of each kind the bell renders, newest first, some unread,
     * spread across Today / This week / Earlier. Rows are written in the exact
     * payload shape FeedNotifier/ProjectCreditService store (message key + params,
     * url, actor), so NotificationResource localizes them like real ones. Replaces
     * the demo talent's previously seeded set (keyed by data.seed).
     *
     * @param  Collection<int, Talent>  $peers
     */
    private function seedNotifications(Talent $demo, Collection $peers): void
    {
        $post = Post::published()->where('author_type', 'talent')->where('author_id', $demo->id)->latest('published_at')->first();
        $postUrl = $post ? route('posts.show', $post) : route('feed');
        $actor = fn (Talent $t) => ['key' => 'talent:'.$t->id, 'type' => 'talent', 'id' => $t->id, 'username' => $t->slug, 'name' => $t->display_name];
        $p = $peers->values();
        // The waiting credit's real project, so the message matches the Credits page.
        $waiting = ProjectCredit::with('project')->where('talent_id', $demo->id)->where('status', 'pending')->latest('id')->first();
        $waitingTitle = (string) ($waiting?->project?->title ?? 'Maison Nour — Resort');

        $rows = [
            ['Feed\\MentionedInPost', ':name mentioned you in a post', $p[0], $postUrl, 0.2, false, 1],
            ['Credits\\ProjectCreditRequested', ':name credited you on ":project"', $p[9], route('talent.credits'), 1, false, 1],
            ['Feed\\PostCommented', ':name commented on your post', $p[1], $postUrl, 3, false, 1],
            ['Feed\\MentionedInComment', ':name mentioned you in a comment', $p[2], $postUrl, 5, false, 1],
            ['Feed\\NewFollower', ':name started following you', $p[4], url($p[4]->slug), 26, true, 1],
            ['Feed\\PostLiked', ':name liked your post', $p[3], $postUrl, 30, true, 13],
            ['Feed\\CommentReplied', ':name replied to your comment', $p[5], $postUrl, 70, true, 1],
            ['Credits\\ProjectCreditAccepted', ':name confirmed your credit on ":project"', $p[1], route('talent.projects'), 150, true, 1],
            ['Feed\\NewFollower', ':name started following you', $p[6], url($p[6]->slug), 340, true, 1],
        ];

        DB::table('notifications')->where('notifiable_type', 'talent')->where('notifiable_id', $demo->id)
            ->where('data->seed', 'screens')->delete();

        foreach ($rows as [$class, $message, $who, $url, $hoursAgo, $read, $count]) {
            $project = $class === 'Credits\\ProjectCreditRequested' ? $waitingTitle : 'Zeina Swim — SS27';
            $at = now()->subMinutes((int) round($hoursAgo * 60));
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\'.$class,
                'notifiable_type' => 'talent',
                'notifiable_id' => $demo->id,
                'data' => json_encode([
                    'seed' => 'screens',
                    'message' => $message,
                    'params' => ['name' => $who->display_name, 'project' => $project],
                    'url' => $url,
                    'actor' => $actor($who),
                    'actors_count' => $count,
                ], JSON_UNESCAPED_UNICODE),
                'read_at' => $read ? $at->copy()->addHour() : null,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
    }

    /**
     * A second demo talent who is Private, unverified and half set up — the
     * "Your profile is Private" notice, the go-Live steps and the private preview.
     * Login: talent-new@fama.test / password.
     */
    private function seedPrivateTalent(): void
    {
        if (Talent::withTrashed()->where('email', 'talent-new@fama.test')->exists()) {
            return;
        }

        $talent = Talent::factory()->draft()->unverified()->create([
            'email' => 'talent-new@fama.test',
            'password' => Hash::make('password'),
            'display_name' => 'Nadia Samir',
            'slug' => 'nadia-samir',
            'bio' => null,
            'worldview' => null,
            'base_city' => 'Alexandria',
            'base_country' => 'Egypt',
        ]);

        $skill = TalentType::where('slug', 'photography')->first() ?? TalentType::first();
        if ($skill) {
            app(SkillsService::class)->addType($talent, $skill);
        }
    }

    /**
     * The prototype's own sample photos (public/fama-ui/img/banners/*.jpg — the
     * images fama-ui's preview/projects/project pages rotate through) replace the
     * demo talent's generated covers, galleries, digitals, showreel thumbnails and
     * post photos, so the demo account looks like the HTML design. The avatar is
     * removed: the prototype's demo talent shows initials. Idempotent — media
     * already carrying the `fama_ui` custom property is left alone.
     */
    private function usePrototypePhotos(Talent $demo): void
    {
        $order = ['dashboard', 'feed', 'projects', 'activity', 'credits', 'notifications', 'profile-structure', 'signin', 'profile'];
        $photos = array_values(array_filter(array_map(fn ($n) => public_path("fama-ui/img/banners/{$n}.jpg"), $order), 'is_file'));

        if ($photos === []) {
            return;
        }

        $next = 0;
        $put = function ($model, string $collection, int $count) use ($photos, &$next): void {
            $first = $model->getFirstMedia($collection);
            if ($first && $first->getCustomProperty('fama_ui')) {
                return;
            }
            $count = max($count, 1);
            $model->clearMediaCollection($collection);
            for ($k = 0; $k < $count; $k++) {
                $model->addMedia($photos[$next++ % count($photos)])
                    ->preservingOriginal()
                    ->withCustomProperties(['fama_ui' => true, 'alt' => ''])
                    ->toMediaCollection($collection);
            }
        };

        $demo->clearMediaCollection('avatar');

        foreach ($demo->projects()->with('media')->orderBy('position')->get() as $project) {
            $put($project, 'cover', 1);
            $put($project, 'media', max(4, $project->getMedia('media')->count()));
        }
        foreach ($demo->portfolioItems()->with('media')->get() as $item) {
            $put($item, 'gallery', 1);
        }
        foreach ($demo->digitals()->with('media')->get() as $digital) {
            $put($digital, 'digital', 1);
        }
        foreach ($demo->showreels()->with('media')->get() as $reel) {
            $put($reel, 'thumbnail', 1);
        }
        Post::with('media')->where('author_type', 'talent')->where('author_id', $demo->id)->whereHas('media')->get()
            ->each(fn (Post $post) => $put($post, 'post_media', $post->getMedia('post_media')->count()));
    }
}
