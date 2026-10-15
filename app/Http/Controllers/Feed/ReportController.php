<?php

namespace App\Http\Controllers\Feed;

use App\Http\Requests\Feed\StoreReportRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReport;
use App\Services\FeedModerationService;
use Illuminate\Http\JsonResponse;

/**
 * Content reporting (feed-spec §10): any Actor, once per reportable per
 * reporter (policy + unique index). The auto-hide threshold sweep and the
 * admin queue land with Prompt 5Q.
 */
class ReportController extends FeedBaseController
{
    /**
     * Report a post.
     */
    public function post(Post $post, StoreReportRequest $request): JsonResponse
    {
        return $this->store($post, $request);
    }

    /**
     * Report a comment.
     */
    public function comment(Comment $comment, StoreReportRequest $request): JsonResponse
    {
        return $this->store($comment, $request);
    }

    /**
     * Persist one report against the target.
     */
    private function store(Post|Comment $reportable, StoreReportRequest $request): JsonResponse
    {
        $this->gate()->authorize('create', [PostReport::class, $reportable]);

        $actor = $this->currentActor->resolve();

        PostReport::query()->create([
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reporter_type' => $actor->getMorphClass(),
            'reporter_id' => $actor->getKey(),
            'reason' => $request->string('reason')->toString(),
            'note' => $request->filled('note') ? $request->string('note')->toString() : null,
            'status' => 'open',
        ]);

        // The auto-hide tripwire (5Q): enough open reports hide the item
        // pending review.
        app(FeedModerationService::class)->registerReport($reportable);

        return response()->success(message: __('Thanks — our team will take a look.'), status: 201);
    }
}
