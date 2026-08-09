<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\TalentType;
use App\Services\FollowManagementService;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The talent's consolidated "Activity" surface — one page, three tabs (Saved,
 * Liked, Following), replacing the three separate sidebar entries. The top tab
 * is `?view=`; the Following tab keeps its own FOLLOWING/FOLLOWERS sub-toggle on
 * `?tab=` (feedFollows). Content is served by the existing collection/follows
 * partials + their Ajax data endpoints — this only chooses which shell to render.
 */
class FeedActivityController extends Controller
{
    public function __construct(private readonly FollowManagementService $follows) {}

    public function index(Request $request): View
    {
        $talent = $request->user('talent');
        $view = in_array($request->query('view'), ['saved', 'liked', 'following'], true)
            ? $request->query('view')
            : 'saved';

        $data = ['view' => $view];

        if ($view === 'following') {
            $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';
            $data += [
                'tab' => $tab,
                // The Talent/Brand type filter is only meaningful when brands can be followed.
                'showTypeFilter' => Features::brands(),
                'skills' => $this->follows->presentSkills($talent, $tab === 'followers' ? 'follower' : 'followable'),
                'dataUrl' => route('talent.feed.follows.data'),
            ];
        } else {
            $data += [
                'skills' => TalentType::query()->orderBy('id')->get(['id', 'name', 'slug']),
                'dataUrl' => route('talent.feed.collection.data'),
            ];
        }

        return view('talent.feed.activity', $data);
    }
}
