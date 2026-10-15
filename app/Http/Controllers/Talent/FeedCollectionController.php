<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\TalentType;
use App\Services\FeedCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The talent's private feed collections (5X, ADR-F9): SAVED and LIKED —
 * scoped to the authenticated talent, no other actor's collection is
 * reachable. Blade shells + Ajax data endpoints.
 */
class FeedCollectionController extends Controller
{
    public function __construct(private readonly FeedCollectionService $collections) {}

    /**
     * The SAVED page shell.
     */
    public function saved(): View
    {
        return view('talent.feed.collection', [
            'mode' => 'saved',
            'skills' => TalentType::query()->orderBy('id')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * The LIKED page shell.
     */
    public function liked(): View
    {
        return view('talent.feed.collection', [
            'mode' => 'liked',
            'skills' => TalentType::query()->orderBy('id')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Collection rows (Ajax): ?mode=saved|liked&skill=&q=&page=.
     */
    public function data(Request $request): JsonResponse
    {
        $talent = $request->user('talent');
        $mode = $request->query('mode') === 'liked' ? 'liked' : 'saved';

        $posts = $mode === 'liked'
            ? $this->collections->liked($talent, $request->query('skill'), $request->query('q'))
            : $this->collections->saved($talent, $request->query('skill'), $request->query('q'));

        // fama-ui Activity: ?format=html returns the canonical post cards.
        if ($request->query('format') === 'html') {
            $this->collections->withCardGraph($posts, $talent);

            return response()->paginated($posts, $posts->getCollection()
                ->map(fn ($post): array => [
                    'id' => $post->id,
                    'html' => view('feed.partials.post', ['post' => $post, 'currentActor' => $talent])->render(),
                ])->values());
        }

        return response()->paginated($posts, $this->collections->present($posts));
    }
}
