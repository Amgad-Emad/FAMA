<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\ResolveLinkPreview;
use App\Http\Resources\Feed\LinkChipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The composer's on-paste chip endpoint (feed-spec §8): POST a URL, get the
 * instantly-parsed chip payload back (provider + handle/resource); enrichment
 * lands asynchronously. Throttled 20/min per actor at the route.
 */
class LinkPreviewController extends FeedBaseController
{
    /**
     * Resolve (find-or-create) the preview for a pasted URL.
     */
    public function __invoke(Request $request, ResolveLinkPreview $resolvePreview): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ]);

        $preview = $resolvePreview($validated['url']);

        return response()->success(new LinkChipResource($preview));
    }
}
