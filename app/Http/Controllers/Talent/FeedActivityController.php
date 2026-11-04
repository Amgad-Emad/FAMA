<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Services\FeedCollectionService;
use App\Services\FollowManagementService;
use App\Support\Features;
use App\Support\FeedComposer;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The talent's private "Activity" page (fama-ui following.html): Saved ·
 * Liked · Following tabs, the Following tab with its Following / Followers
 * sub-tabs. One shell; every list loads over Ajax from the existing data
 * endpoints (Saved/Liked as full post cards, people as rows) and the tabs
 * switch in place (?view= / ?tab= are kept in the URL for deep links). With
 * no ?view= it opens on Saved, as the prototype does.
 */
class FeedActivityController extends Controller
{
    /**
     * Inject the follow-graph and collection services.
     */
    public function __construct(
        private readonly FollowManagementService $follows,
        private readonly FeedCollectionService $collections,
    ) {}

    /**
     * Render the Activity shell with the tab counts and both sides' skill
     * filter options (so switching sub-tab needs no reload).
     */
    public function index(Request $request): View
    {
        $talent = $request->user('talent');
        $view = in_array($request->query('view'), ['saved', 'liked', 'following'], true)
            ? $request->query('view')
            : 'saved'; // the prototype opens Activity on Saved (following.html, 1 Nov 2026)
        $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';

        return view('talent.feed.activity', [
            'view' => $view,
            'tab' => $tab,
            // The Talent/Brand type split only matters when brands can be followed.
            'showTypeFilter' => Features::brands(),
            'counts' => $this->collections->counts($talent) + [
                'following' => (int) $talent->following_count,
                'followers' => (int) $talent->followers_count,
            ],
            'followingSkills' => $this->follows->presentSkills($talent, 'followable'),
            'followerSkills' => $this->follows->presentSkills($talent, 'follower'),
            'collectionUrl' => route('talent.feed.collection.data'),
            'followsUrl' => route('talent.feed.follows.data'),
            // Saved/Liked cards include the talent's own posts — Edit works here too.
            'composer' => FeedComposer::for($talent),
        ]);
    }
}
