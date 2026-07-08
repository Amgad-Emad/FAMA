<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Campaign;
use App\Models\Review;
use App\Models\Talent;
use App\Services\BrandModerationService;
use App\Services\CampaignOversightService;
use App\Services\ReviewModerationService;
use App\Services\TalentModerationService;
use Illuminate\Http\JsonResponse;
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
        private readonly CampaignOversightService $campaignMod,
    ) {}

    public function index(): View
    {
        return view('admin.moderation.index');
    }

    // --- Talents ------------------------------------------------------------

    public function talents(): JsonResponse
    {
        $paginator = Talent::query()->withTrashed()->latest()->paginate(20);

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

    public function moderateTalent(Request $request, Talent $talent, string $action): JsonResponse
    {
        $admin = $this->admin();
        $reason = $request->input('reason');

        match ($action) {
            'suspend' => $this->talentMod->suspend($admin, $talent, $reason),
            'unpublish' => $this->talentMod->unpublish($admin, $talent, $reason),
            'restore' => $this->talentMod->restore($admin, $talent),
            'delete' => $this->talentMod->softDelete($admin, $talent, $reason),
            default => abort(404),
        };

        return response()->success(null, __('Talent moderated.'));
    }

    // --- Talent reviews -----------------------------------------------------

    public function reviews(): JsonResponse
    {
        $paginator = Review::query()->where('status', 'pending')->with('talent')->latest()->paginate(20);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (Review $r) => [
            'id' => $r->id,
            'body' => $r->body,
            'rating' => $r->rating,
            'talent' => $r->talent?->display_name,
            'status' => $r->status->getValue(),
        ]));
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

    public function brands(): JsonResponse
    {
        $paginator = Brand::query()->withTrashed()->latest()->paginate(20);

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

    public function moderateBrand(Request $request, Brand $brand, string $action): JsonResponse
    {
        $admin = $this->admin();
        $reason = $request->input('reason');

        match ($action) {
            'verify' => $this->brandMod->verify($admin, $brand),
            'suspend' => $this->brandMod->suspend($admin, $brand, $reason),
            'unpublish' => $this->brandMod->unpublish($admin, $brand, $reason),
            'delete' => $this->brandMod->softDelete($admin, $brand, $reason),
            default => abort(404),
        };

        return response()->success(null, __('Brand moderated.'));
    }

    // --- Brand reviews ------------------------------------------------------

    public function brandReviews(): JsonResponse
    {
        $paginator = BrandReview::query()->where('status', 'pending')->with(['brand', 'talent'])->latest()->paginate(20);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (BrandReview $r) => [
            'id' => $r->id,
            'body' => $r->body,
            'average_rating' => $r->average_rating,
            'brand' => $r->brand?->name,
            'talent' => $r->talent?->display_name,
        ]));
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

    // --- Campaigns ----------------------------------------------------------

    public function campaigns(Request $request): JsonResponse
    {
        $paginator = $this->campaignMod->forStatus($this->admin(), $request->query('status'));

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (Campaign $c) => [
            'id' => $c->id,
            'title' => $c->title,
            'slug' => $c->slug,
            'status' => $c->status->getValue(),
            'is_public' => (bool) $c->is_public,
            'brand' => $c->brand?->name,
            'deals_count' => $c->deals_count,
        ]));
    }

    public function moderateCampaign(Request $request, Campaign $campaign, string $action): JsonResponse
    {
        $admin = $this->admin();
        $reason = $request->input('reason');

        match ($action) {
            'cancel' => $this->campaignMod->cancel($admin, $campaign, $reason),
            'private' => $this->campaignMod->forcePrivate($admin, $campaign, $reason),
            default => abort(404),
        };

        return response()->success(null, __('Campaign moderated.'));
    }
}
