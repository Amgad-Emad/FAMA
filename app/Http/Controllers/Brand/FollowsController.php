<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Services\FollowManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The brand's follow-graph management surface (Prompt 5-follow). Identical to
 * the talent version but with the type filter hidden — brands follow only
 * talents, so every followed account is a talent already.
 */
class FollowsController extends Controller
{
    public function __construct(private readonly FollowManagementService $follows) {}

    /**
     * The management page shell (FOLLOWING default, ?tab=followers switches).
     */
    public function index(Request $request): View
    {
        $brand = $request->user('brand');
        $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';
        $side = $tab === 'followers' ? 'follower' : 'followable';

        return view('brand.feed.follows', [
            'tab' => $tab,
            'showTypeFilter' => false,
            'skills' => $this->follows->presentSkills($brand, $side),
            'dataUrl' => route('brand.feed.follows.data'),
        ]);
    }

    /**
     * Rows (Ajax): ?tab=following|followers&q=&skill=&sort=&page=.
     */
    public function data(Request $request): JsonResponse
    {
        $brand = $request->user('brand');
        $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';

        $filters = $request->only(['q', 'type', 'skill', 'sort']);

        $result = $tab === 'followers'
            ? $this->follows->followers($brand, $filters)
            : $this->follows->following($brand, $filters);

        return response()->paginated($result['paginator'], $result['rows']);
    }
}
