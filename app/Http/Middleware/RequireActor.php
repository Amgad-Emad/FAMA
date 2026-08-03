<?php

namespace App\Http\Middleware;

use App\Feed\CurrentActor;
use App\Feed\PendingIntent;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards feed WRITE routes (ADR-F8). A guest gets a 401 JSON envelope whose
 * data carries the intended action — {action, target_type, target_id} — so
 * the frontend can expand the control into the inline sign-in prompt; the
 * intent (plus the write payload, e.g. a typed comment body) is stored in the
 * session for exactly-once replay after login. Never a redirect, never a
 * blocking modal.
 *
 * An admin-only session gets a plain 403 — admins moderate, they don't
 * participate, and there is nothing to replay.
 *
 * Usage: ->middleware('require.actor:like') — the parameter names the action.
 */
class RequireActor
{
    public function __construct(
        private readonly CurrentActor $currentActor,
        private readonly PendingIntent $pendingIntent,
    ) {
    }

    /**
     * Let actors through; capture-and-401 guests; 403 admin-only sessions.
     */
    public function handle(Request $request, Closure $next, string $action = 'act'): Response
    {
        if ($this->currentActor->check()) {
            return $next($request);
        }

        if (Auth::guard('admin')->check()) {
            return ApiResponse::error(message: __('Admins moderate the feed but cannot participate.'), status: 403);
        }

        $intent = $this->buildIntent($request, $action);

        // Intent replay is a session concept — the stateless API mirror just
        // gets the 401 envelope. Alongside the intent we remember WHERE the
        // guest was (the post anchor), so login lands them back on the exact
        // frame with the action already replayed (ADR-F8) — the feed's own
        // return slot, deliberately separate from url.intended (which the
        // multi-guard login ignores).
        if ($request->hasSession()) {
            $this->pendingIntent->capture($intent);
            $request->session()->put('feed.return_to', $this->returnUrl($request, $intent));
        }

        return ApiResponse::error(
            message: __('Sign in to continue.'),
            status: 401,
            data: ['intent' => $intent],
        );
    }

    /**
     * Where to land after login: the exact post anchor when the target is a
     * post/comment, else the same-origin page the guest was on.
     *
     * @param  array{action: string, target_type: string, target_id: int|string}  $intent
     */
    private function returnUrl(Request $request, array $intent): string
    {
        if ($intent['target_type'] === 'post' && $intent['target_id']) {
            return route('posts.show', $intent['target_id']).'#post-'.$intent['target_id'];
        }

        if ($intent['target_type'] === 'comment' && $intent['target_id']) {
            $comment = \App\Models\Comment::query()->find($intent['target_id']);

            if ($comment !== null) {
                return route('posts.show', $comment->post_id).'#comment-'.$comment->id;
            }
        }

        $referer = (string) $request->headers->get('referer');

        return str_starts_with($referer, url('/')) ? $referer : route('feed');
    }

    /**
     * Derive the intent from the route: the first bound model (or explicit
     * type/id parameters, e.g. /follow/{type}/{id}) is the target; the request
     * body rides along as the replay payload.
     *
     * @return array{action: string, target_type: string, target_id: int|string, payload: array<string, mixed>}
     */
    private function buildIntent(Request $request, string $action): array
    {
        $targetType = '';
        $targetId = 0;

        foreach ($request->route()->parameters() as $name => $value) {
            if ($value instanceof Model) {
                $targetType = $value->getMorphClass();
                $targetId = $value->getKey();
                break;
            }
        }

        if ($targetType === '' && $request->route('type') !== null) {
            $targetType = (string) $request->route('type');
            $targetId = (int) $request->route('id');
        }

        return [
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $request->only(['body', 'parent_id']),
        ];
    }
}
