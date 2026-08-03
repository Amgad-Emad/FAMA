<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\ShareProjectAsPost;
use App\Http\Requests\Feed\ShareProjectRequest;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;

/**
 * POST /projects/{project}/share — share a talent's own Project into the feed
 * (feed-spec §4, ADR-F2). Ownership/visibility rules live in the action; the
 * policy gates that the actor may author posts at all.
 */
class ProjectShareController extends FeedBaseController
{
    /**
     * Share the Project as a project_share post.
     */
    public function __invoke(Project $project, ShareProjectRequest $request, ShareProjectAsPost $shareProject): JsonResponse
    {
        $this->gate()->authorize('create', Post::class);

        $actor = $this->currentActor->resolve();
        abort_unless($actor instanceof Talent, 403);

        $post = $shareProject(
            $actor,
            $project,
            $request->filled('body') ? $request->string('body')->toString() : null,
            array_map(intval(...), $request->input('skill_ids', [])),
        );

        return response()->success($post, status: 201);
    }
}
