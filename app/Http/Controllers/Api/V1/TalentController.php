<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TalentProfileViewed;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\Api\V1\TalentResource;
use App\Http\Resources\TalentCardResource;
use App\Models\Project;
use App\Models\Talent;
use App\Queries\TalentSearch;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

/**
 * @group Discovery
 *
 * Public, read-only talent discovery for the mobile app plus the two public
 * write actions a visitor can take on a profile (leave a review, send a booking
 * enquiry) — the same query, services and validation the web pages use.
 * Published talents only.
 */
class TalentController extends Controller
{
    public function __construct(private readonly TalentSearch $search) {}

    /**
     * List talents
     *
     * Paginated, filterable discovery feed. Supply `filter[...]` params
     * (type, category, availability, city, country, equipment, software, q) and
     * `sort` (view_count, created_at) exactly as the web feed does.
     *
     * @unauthenticated
     *
     * @queryParam filter[type] string Comma-separated profession slugs. Example: photographer
     * @queryParam filter[city] string Partial city match. Example: Cairo
     * @queryParam filter[availability] string One of available, booked, unavailable. Example: available
     * @queryParam filter[q] string Free-text display-name match. Example: amgad
     * @queryParam sort string view_count or created_at (prefix with - for descending). Example: -view_count
     * @queryParam page integer The page number. Example: 1
     */
    public function index(): JsonResponse
    {
        $paginator = $this->search->paginate();

        return response()->paginated($paginator, TalentCardResource::collection($paginator->getCollection()));
    }

    /**
     * Show a talent
     *
     * The full public passport for one published talent, resolved by slug: the
     * visible profile blocks, profession(s), comp card, rate-card services and
     * approved reviews. Translatable fields come back in the request locale
     * (Accept-Language). Bumps the profile view counter.
     *
     * @unauthenticated
     */
    public function show(Talent $talent): JsonResponse
    {
        abort_unless((bool) $talent->is_published, 404);

        $talent->load([
            'talentTypes',
            'compCard',
            'profileBlocks' => fn ($query) => $query->where('is_visible', true),
            'profileBlocks.blockType',
            'services' => fn ($query) => $query->where('is_active', true),
            'reviews' => fn ($query) => $query->where('is_approved', true),
        ]);

        TalentProfileViewed::dispatch($talent);

        return response()->success(new TalentResource($talent));
    }

    /**
     * Show a case study
     *
     * One published talent's project (case study), resolved by id and scoped to
     * the talent in the path (404 otherwise).
     *
     * @unauthenticated
     */
    public function project(Talent $talent, Project $project): JsonResponse
    {
        abort_unless((bool) $talent->is_published, 404);
        abort_unless((int) $project->talent_id === (int) $talent->getKey(), 404);

        $project->load('media');

        return response()->success([
            'id' => $project->id,
            'title' => $project->getTranslations('title'),
            'client_name' => $project->client_name,
            'summary' => $project->getTranslations('summary'),
            'body' => $project->getTranslations('body'),
            'year' => $project->year,
            'url' => $project->url,
            'cover_image_url' => $project->cover_image_url,
        ]);
    }

    /**
     * Submit a review
     *
     * A past client leaves a review on a published talent; it lands pending for
     * the talent to moderate.
     *
     * @unauthenticated
     */
    public function submitReview(StoreReviewRequest $request, Talent $talent): JsonResponse
    {
        abort_unless((bool) $talent->is_published, 404);

        $talent->reviews()->create($request->validated() + [
            'is_approved' => false,
            'status' => 'pending',
            'reviewed_at' => now(),
        ]);

        return response()->success(null, __('Thank you — your review has been submitted for approval.'), status: 201);
    }

    /**
     * Send a booking enquiry
     *
     * The public booking CTA — lands in `deal_enquiries` (availability-checked)
     * and converts to a deal once a brand picks it up.
     *
     * @unauthenticated
     */
    public function submitEnquiry(StoreEnquiryRequest $request, Talent $talent): JsonResponse
    {
        abort_unless((bool) $talent->is_published, 404);

        if ($talent->availability_status->getValue() === 'unavailable') {
            throw new InvalidArgumentException(__('This talent is not currently taking bookings.'));
        }

        $talent->dealEnquiries()->create($request->validated() + ['status' => 'new']);

        return response()->success(null, __('Your enquiry has been sent. The talent will be in touch.'), status: 201);
    }
}
