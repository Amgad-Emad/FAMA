<?php

namespace App\Http\Controllers\Feed;

use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The follow-suggestion rails' endpoints (5Z): public reads paginated by an
 * offset cursor (Show more appends), and the dismiss write (actor-only,
 * 60/min). Guests get seeded, stable-per-session suggestions.
 */
class SuggestionController extends FeedBaseController
{
    /**
     * Talent suggestions: ?offset=0&limit=5 → {items, next_offset}.
     */
    public function talents(Request $request, SuggestionService $suggestions): JsonResponse
    {
        return $this->serve($request, $suggestions, 'talents');
    }

    /**
     * Brand suggestions (empty for brand viewers — the rail hides).
     */
    public function brands(Request $request, SuggestionService $suggestions): JsonResponse
    {
        return $this->serve($request, $suggestions, 'brands');
    }

    /**
     * Permanently dismiss one suggestion: {type: talent|brand, id}.
     */
    public function dismiss(Request $request, SuggestionService $suggestions): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:talent,brand'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $suggestions->dismiss($this->currentActor->resolve(), $validated['type'], (int) $validated['id']);

        return response()->success(message: __('We won’t suggest them again.'));
    }

    /**
     * One rail page + the shown-marking that powers the 7-day rest rule.
     */
    private function serve(Request $request, SuggestionService $suggestions, string $type): JsonResponse
    {
        $viewer = $this->currentActor->resolve();
        $limit = min(20, max(1, (int) $request->query('limit', 5)));
        $offset = max(0, (int) $request->query('offset', 0));
        $seed = $viewer === null ? $this->guestSeed($request) : '';

        $page = $type === 'talents'
            ? $suggestions->talentsFor($viewer, $limit, $offset, $seed)
            : $suggestions->brandsFor($viewer, $limit, $offset, $seed);

        $suggestions->markShown($viewer, $type, array_column($page['items'], 'id'));

        // The rails swap server-rendered cards (the repo's fragment
        // convention); the API mirror stays pure JSON.
        if (! $request->is('api/*') && $request->query('format') === 'html' && $page['items'] !== []) {
            $page['html'] = view('feed.partials.suggestion-cards', [
                'type' => $type === 'talents' ? 'talent' : 'brand',
                'items' => $page['items'],
            ])->render();
        }

        return response()->success($page);
    }

    /**
     * Stable per-visitor guest seed: minted once and STORED in the session
     * (session IDs regenerate; stored data survives), so a refresh keeps the
     * order while two visitors differ. Stateless API calls fall back to a
     * client fingerprint.
     */
    private function guestSeed(Request $request): string
    {
        if (! $request->hasSession()) {
            return substr(sha1('suggest-seed:'.$request->ip().'|'.$request->userAgent()), 0, 16);
        }

        $seed = $request->session()->get('feed.suggest_seed');

        if (! is_string($seed)) {
            $seed = substr(sha1('suggest-seed:'.$request->session()->getId()), 0, 16);
            $request->session()->put('feed.suggest_seed', $seed);
        }

        return $seed;
    }
}
