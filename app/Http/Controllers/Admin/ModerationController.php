<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\BrandProject;
use App\Models\Review;
use App\Models\Talent;
use App\Services\BrandModerationService;
use App\Services\ProjectOversightService;
use App\Services\ReviewModerationService;
use App\Services\TalentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin moderation queues (Phase 3B UI). One shell with tabbed queues; each
 * queue GET returns paginated JSON, each action delegates to a Phase 3A
 * moderation service (which authorizes + audits). `can:moderate-content` gates
 * the routes.
 */
class ModerationController extends AdminController
{
    public function __construct(
        private readonly TalentModerationService $talentMod,
        private readonly BrandModerationService $brandMod,
        private readonly ReviewModerationService $reviewMod,
        private readonly ProjectOversightService $projectMod,
    ) {}

    /**
     * The moderation shell. `?queue=` deep-links a specific tab (the sidebar
     * links each queue directly); an unknown value falls back to `talents`.
     */
    public function index(Request $request): View
    {
        $queues = ['talents', 'all-reviews', 'reviews', 'brands', 'brand-reviews', 'projects'];
        $queue = $request->query('queue');

        return view('admin.moderation.index', [
            'queue' => in_array($queue, $queues, true) ? $queue : 'talents',
        ]);
    }

    // --- Talents ------------------------------------------------------------

    public function talents(Request $request): JsonResponse
    {
        $paginator = Talent::query()->withTrashed()
            ->when($request->query('q'), fn ($query, $q) => $query->where(fn ($w) => $w
                ->where('display_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(20);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (Talent $t) => [
            'id' => $t->id,
            'display_name' => $t->display_name,
            'slug' => $t->slug,
            'status' => $t->status->getValue(),
            'is_published' => (bool) $t->is_published,
            'trashed' => $t->trashed(),
            'city' => $t->base_city,
        ]));
    }

    /**
     * Full detail for one talent in the queue (the moderation drawer): identity,
     * bio, skills, pricing, publish state, and content counts — enough to decide
     * a moderation action without leaving the queue.
     */
    public function showTalent(Talent $talent): JsonResponse
    {
        $talent->load(['talentTypes', 'media'])->loadCount(['profileBlocks', 'projects', 'reviews']);

        return response()->success([
            'id' => $talent->id,
            'display_name' => $talent->display_name,
            'slug' => $talent->slug,
            'avatar_url' => $talent->avatar_url,
            'email' => $talent->email,
            'phone' => $talent->phone,
            'headline' => $talent->headline,
            'bio' => $talent->bio,
            'city' => $talent->base_city,
            'country' => $talent->base_country,
            'skills' => $talent->talentTypes->map(fn ($t) => $t->getTranslation('name', app()->getLocale()))->values(),
            'rate' => $talent->rate_amount !== null ? trim($talent->rate_amount.' '.$talent->rate_currency.' / '.$talent->rate_unit) : null,
            'status' => $talent->status->getValue(),
            'is_published' => (bool) $talent->is_published,
            'trashed' => $talent->trashed(),
            'view_count' => (int) $talent->view_count,
            'blocks_count' => $talent->profile_blocks_count,
            'projects_count' => $talent->projects_count,
            'reviews_count' => $talent->reviews_count,
            'public_url' => $talent->is_published && ! $talent->trashed() ? url('/'.$talent->slug) : null,
            'created_at' => $talent->created_at?->toDayDateTimeString(),
        ]);
    }

    public function moderateTalent(Request $request, Talent $talent, string $action): JsonResponse
    {
        $admin = $this->admin();
        $reason = $request->input('reason');

        match ($action) {
            'suspend' => $this->talentMod->suspend($admin, $talent, $reason),
            'unsuspend' => $this->talentMod->unsuspend($admin, $talent, $reason),
            'unpublish' => $this->talentMod->unpublish($admin, $talent, $reason),
            'publish' => $this->talentMod->publish($admin, $talent, $reason),
            'restore' => $this->talentMod->restore($admin, $talent),
            'delete' => $this->talentMod->softDelete($admin, $talent, $reason),
            default => abort(404),
        };

        return response()->success(null, __('Talent moderated.'));
    }

    // --- Talent reviews -----------------------------------------------------

    public function reviews(Request $request): JsonResponse
    {
        $paginator = Review::query()->where('status', 'pending')->with('talent')
            ->when($request->query('q'), fn ($query, $q) => $query->where(fn ($w) => $w
                ->where('body', 'like', "%{$q}%")->orWhere('reviewer_name', 'like', "%{$q}%")
                ->orWhereHas('talent', fn ($t) => $t->where('display_name', 'like', "%{$q}%"))))
            ->latest()->paginate(20);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (Review $r) => [
            'id' => $r->id,
            'body' => $r->body,
            'rating' => $r->rating,
            'talent' => $r->talent?->display_name,
            'status' => $r->status->getValue(),
        ]));
    }

    /**
     * Full detail for one talent review: the complete body, who wrote it and
     * about whom — the flat queue row truncates all of this.
     */
    public function showReview(Review $review): JsonResponse
    {
        $review->load('talent');

        return response()->success([
            'id' => $review->id,
            'kind' => 'talent',
            'body' => $review->body,
            'rating' => $review->rating,
            'reviewer_name' => $review->reviewer_name,
            'reviewer_role' => $review->reviewer_role,
            'reviewer_company' => $review->reviewer_company,
            'project_type' => $review->project_type,
            'talent' => $review->talent?->display_name,
            'talent_slug' => $review->talent?->slug,
            'status' => $review->status->getValue(),
            'created_at' => $review->created_at?->toDayDateTimeString(),
        ]);
    }

    public function moderateReview(Review $review, string $action): JsonResponse
    {
        match ($action) {
            'approve' => $this->reviewMod->approve($this->admin(), $review),
            'reject' => $this->reviewMod->reject($this->admin(), $review),
            default => abort(404),
        };

        return response()->success(null, __('Review moderated.'));
    }

    public function batchReviews(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $data['action'] === 'approve'
            ? $this->reviewMod->approveBatch($this->admin(), $data['ids'])
            : $this->reviewMod->rejectBatch($this->admin(), $data['ids']);

        return response()->success(['count' => $count], __(':n reviews moderated.', ['n' => $count]));
    }

    // --- Brands -------------------------------------------------------------

    public function brands(Request $request): JsonResponse
    {
        $paginator = Brand::query()->withTrashed()
            ->when($request->query('q'), fn ($query, $q) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(20);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (Brand $b) => [
            'id' => $b->id,
            'name' => $b->name,
            'slug' => $b->slug,
            'status' => $b->status->getValue(),
            'is_verified' => (bool) $b->is_verified,
            'is_published' => (bool) $b->is_published,
            'trashed' => $b->trashed(),
        ]));
    }

    /**
     * Full detail for one brand: identity, description, credibility and counts —
     * what a moderator needs to verify or act without leaving the queue.
     */
    public function showBrand(Brand $brand): JsonResponse
    {
        $brand->load(['media', 'credibility'])->loadCount(['projects', 'brandReviews']);

        return response()->success([
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'logo_url' => $brand->logo_url,
            'email' => $brand->email,
            'phone' => $brand->phone,
            'description' => $brand->description,
            'industry' => $brand->industry,
            'stage' => $brand->brand_stage,
            'city' => $brand->base_city,
            'country' => $brand->base_country,
            'website' => $brand->website,
            'founded_year' => $brand->founded_year,
            'company_size' => $brand->company_size,
            'status' => $brand->status->getValue(),
            'is_verified' => (bool) $brand->is_verified,
            'is_published' => (bool) $brand->is_published,
            'trashed' => $brand->trashed(),
            'completed_projects' => $brand->credibility?->completed_projects_count,
            'response_rate_pct' => $brand->credibility?->response_rate_pct,
            'projects_count' => $brand->projects_count,
            'reviews_count' => $brand->brand_reviews_count,
            'public_url' => $brand->is_published && ! $brand->trashed() ? url('/brands/'.$brand->slug) : null,
            'created_at' => $brand->created_at?->toDayDateTimeString(),
        ]);
    }

    public function moderateBrand(Request $request, Brand $brand, string $action): JsonResponse
    {
        $admin = $this->admin();
        $reason = $request->input('reason');

        match ($action) {
            'verify' => $this->brandMod->verify($admin, $brand),
            'suspend' => $this->brandMod->suspend($admin, $brand, $reason),
            'unsuspend' => $this->brandMod->unsuspend($admin, $brand, $reason),
            'unpublish' => $this->brandMod->unpublish($admin, $brand, $reason),
            'publish' => $this->brandMod->publish($admin, $brand, $reason),
            'delete' => $this->brandMod->softDelete($admin, $brand, $reason),
            default => abort(404),
        };

        return response()->success(null, __('Brand moderated.'));
    }

    // --- Brand reviews ------------------------------------------------------

    public function brandReviews(Request $request): JsonResponse
    {
        $paginator = BrandReview::query()->where('status', 'pending')->with(['brand', 'talent'])
            ->when($request->query('q'), fn ($query, $q) => $query->where(fn ($w) => $w
                ->where('body', 'like', "%{$q}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"))
                ->orWhereHas('talent', fn ($t) => $t->where('display_name', 'like', "%{$q}%"))))
            ->latest()->paginate(20);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (BrandReview $r) => [
            'id' => $r->id,
            'body' => $r->body,
            'average_rating' => $r->average_rating,
            'brand' => $r->brand?->name,
            'talent' => $r->talent?->display_name,
        ]));
    }

    /**
     * Full detail for one brand review: the body, the three sub-ratings, and
     * both parties (plus the originating contract's reference when linked).
     */
    public function showBrandReview(BrandReview $review): JsonResponse
    {
        $review->load(['brand', 'talent', 'contract']);

        return response()->success([
            'id' => $review->id,
            'kind' => 'brand',
            'body' => $review->body,
            'communication_rating' => $review->communication_rating,
            'fairness_rating' => $review->fairness_rating,
            'creative_respect_rating' => $review->creative_respect_rating,
            'average_rating' => $review->average_rating,
            'brand' => $review->brand?->name,
            'brand_slug' => $review->brand?->slug,
            'talent' => $review->talent?->display_name,
            'talent_slug' => $review->talent?->slug,
            'contract_reference' => $review->contract?->reference,
            'status' => $review->status->getValue(),
            'created_at' => $review->created_at?->toDayDateTimeString(),
        ]);
    }

    public function moderateBrandReview(BrandReview $review, string $action): JsonResponse
    {
        match ($action) {
            'approve' => $this->reviewMod->approveBrandReview($this->admin(), $review),
            'reject' => $this->reviewMod->rejectBrandReview($this->admin(), $review),
            default => abort(404),
        };

        return response()->success(null, __('Brand review moderated.'));
    }

    /**
     * The GLOBAL review queue: every pending review platform-wide — talent
     * `reviews` and `brand_reviews` in one chronological, honestly-paginated
     * list. A UNION of (id, kind, created_at) drives the page; the page's rows
     * are then hydrated per kind with their relations eager-loaded (two
     * queries, no N+1). `kind` routes each row's approve/reject to the
     * matching per-kind endpoint.
     */
    public function allReviews(): JsonResponse
    {
        $union = DB::table('reviews')
            ->select('id', DB::raw("'talent' as kind"), 'created_at')
            ->where('status', 'pending')
            ->unionAll(
                DB::table('brand_reviews')
                    ->select('id', DB::raw("'brand' as kind"), 'created_at')
                    ->where('status', 'pending')
            );

        $paginator = DB::query()->fromSub($union, 'pending_reviews')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(20);

        $page = collect($paginator->items());
        $talentReviews = Review::with('talent')
            ->findMany($page->where('kind', 'talent')->pluck('id'))->keyBy('id');
        $brandReviews = BrandReview::with(['brand', 'talent'])
            ->findMany($page->where('kind', 'brand')->pluck('id'))->keyBy('id');

        return response()->paginated($paginator, $page->map(fn (object $row) => $row->kind === 'talent'
            ? [
                'id' => $row->id,
                'kind' => 'talent',
                'body' => $talentReviews->get($row->id)?->body,
                'rating' => $talentReviews->get($row->id)?->rating,
                'talent' => $talentReviews->get($row->id)?->talent?->display_name,
                'brand' => null,
            ]
            : [
                'id' => $row->id,
                'kind' => 'brand',
                'body' => $brandReviews->get($row->id)?->body,
                'rating' => $brandReviews->get($row->id)?->average_rating,
                'talent' => $brandReviews->get($row->id)?->talent?->display_name,
                'brand' => $brandReviews->get($row->id)?->brand?->name,
            ])->values());
    }

    // --- Projects ----------------------------------------------------------

    public function projects(Request $request): JsonResponse
    {
        $paginator = $this->projectMod->forStatus($this->admin(), $request->query('status'), $request->query('q'));

        // Admin oversight always sees the budget; `budget_is_public` tags
        // whether the public side does too (private by default).
        return response()->paginated($paginator, $paginator->getCollection()->map(fn (BrandProject $c) => [
            'id' => $c->id,
            'title' => $c->title,
            'slug' => $c->slug,
            'status' => $c->status->getValue(),
            'is_public' => (bool) $c->is_public,
            'brand' => $c->brand?->name,
            'contracts_count' => $c->contracts_count,
            'budget_min' => $c->budget_min !== null ? (float) $c->budget_min : null,
            'budget_max' => $c->budget_max !== null ? (float) $c->budget_max : null,
            'currency' => $c->currency,
            'budget_is_public' => (bool) $c->budget_is_public,
        ]));
    }

    /**
     * Full detail for one project: description, role sought, dates, budget
     * (admins always see it — tagged private when `budget_is_public` is off),
     * and the contracts running under it.
     */
    public function showProject(BrandProject $project): JsonResponse
    {
        $project->load(['brand', 'talentType', 'media'])->loadCount(['gallery', 'contracts']);

        return response()->success([
            'id' => $project->id,
            'title' => $project->title,
            'slug' => $project->slug,
            'cover_url' => $project->cover_url,
            'type' => $project->type,
            'description' => $project->description,
            'brand' => $project->brand?->name,
            'brand_slug' => $project->brand?->slug,
            'role' => $project->talentType?->getTranslation('name', app()->getLocale()),
            'city' => $project->location_city,
            'country' => $project->location_country,
            'start_date' => $project->start_date?->toDateString(),
            'end_date' => $project->end_date?->toDateString(),
            'budget_min' => $project->budget_min !== null ? (float) $project->budget_min : null,
            'budget_max' => $project->budget_max !== null ? (float) $project->budget_max : null,
            'currency' => $project->currency,
            'budget_is_public' => (bool) $project->budget_is_public,
            'status' => $project->status->getValue(),
            'is_public' => (bool) $project->is_public,
            'gallery_count' => $project->gallery_count,
            'contracts_count' => $project->contracts_count,
            'public_url' => $project->is_public && $project->brand ? url('/brands/'.$project->brand->slug.'/projects/'.$project->slug) : null,
            'created_at' => $project->created_at?->toDayDateTimeString(),
        ]);
    }

    public function moderateProject(Request $request, BrandProject $project, string $action): JsonResponse
    {
        $admin = $this->admin();
        $reason = $request->input('reason');

        match ($action) {
            'cancel' => $this->projectMod->cancel($admin, $project, $reason),
            'private' => $this->projectMod->forcePrivate($admin, $project, $reason),
            'public' => $this->projectMod->makePublic($admin, $project, $reason),
            default => abort(404),
        };

        return response()->success(null, __('BrandProject moderated.'));
    }
}
