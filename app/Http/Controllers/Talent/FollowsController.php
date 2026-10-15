<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Services\FollowManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The talent's own follow-graph management surface (Prompt 5-follow): the
 * FOLLOWING and FOLLOWERS sub-tabs. Reuses the 5X collection shell — same
 * layout, pagination and voice — over FollowManagementService. Scoped to the
 * authenticated talent; the dashboard-only signals (mutual badge,
 * quiet-since) never reach the public FollowListController payload.
 */
class FollowsController extends Controller
{
    public function __construct(private readonly FollowManagementService $follows) {}

    /**
     * The management page shell (FOLLOWING default, ?tab=followers switches).
     */
    public function index(Request $request): View
    {
        $talent = $request->user('talent');
        $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';
        $side = $tab === 'followers' ? 'follower' : 'followable';

        return view('talent.feed.follows', [
            'tab' => $tab,
            'showTypeFilter' => true,
            'skills' => $this->follows->presentSkills($talent, $side),
            'dataUrl' => route('talent.feed.follows.data'),
        ]);
    }

    /**
     * Rows (Ajax): ?tab=following|followers&q=&type=&skill=&sort=&page=.
     */
    public function data(Request $request): JsonResponse
    {
        $talent = $request->user('talent');
        $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';

        $filters = $request->only(['q', 'type', 'skill', 'sort']);

        $result = $tab === 'followers'
            ? $this->follows->followers($talent, $filters)
            : $this->follows->following($talent, $filters);

        return response()->paginated($result['paginator'], $result['rows']);
    }
}
