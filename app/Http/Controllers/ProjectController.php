<?php

namespace App\Http\Controllers;

use App\Feed\CurrentActor;
use App\Models\Like;
use App\Models\Project;
use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Project detail — `fama.com/{slug}/work/{project}` (talent-spec, public; fama-ui
 * `project.html`). One projects record told as a story. 404 unless the talent is
 * published and the project belongs to them.
 */
class ProjectController extends Controller
{
    /** How many other projects the "more from" strip shows. */
    private const MORE = 4;

    /**
     * Render the project page: the project with its media and public credits,
     * the owner (with their primary skill for the owner band), up to four other
     * projects by the same talent, and whether the viewer liked the project.
     * `?preview=1` from the owner's editor shows the preview strip;
     * `?visitor=1` ("See it as a visitor" in the Made-by band) renders the page
     * an owner would otherwise see with Edit, exactly as a visitor sees it.
     */
    public function show(Request $request, string $slug, Project $project, CurrentActor $actor): View
    {
        $talent = Talent::query()->where('slug', $slug)->where('is_published', true)
            ->with(['media', 'talentTypes'])
            ->firstOrFail();

        abort_unless((int) $project->talent_id === (int) $talent->getKey(), 404);

        // Credits render on this page, so the collaborator + skill relations
        // ride along — a credit block is the most N+1-prone thing here.
        $project->load(['media', 'talentTypes', 'specialities', 'credits.talent.media', 'credits.talentType', 'ownerCredits.talent']);
        $project->setRelation('talent', $talent);

        // `media` eager-loaded: lead_image_url goes through getFirstMediaUrl,
        // which loadMissing()s and slips past preventLazyLoading.
        $more = $talent->projects()
            ->whereKeyNot($project->getKey())
            ->with('media')
            ->orderBy('position')->orderBy('id')
            ->limit(self::MORE)
            ->get();

        $viewer = $actor->resolve();
        // ANY owner (creator or accepted co-owner) gets Edit + the preview strip.
        $isOwner = $viewer instanceof Talent && $project->isOwnedBy($viewer)
            && ! $request->boolean('visitor');
        // Has this viewer liked the project (ADR-AJ)? One indexed lookup.
        $liked = $viewer !== null && Like::query()->where([
            'likeable_type' => $project->getMorphClass(), 'likeable_id' => $project->getKey(),
            'liker_type' => $viewer->getMorphClass(), 'liker_id' => $viewer->getKey(),
        ])->exists();

        return view('public.project', [
            'talent' => $talent,
            'project' => $project,
            'more' => $more,
            'isOwner' => $isOwner,
            'liked' => $liked,
            'preview' => $isOwner && $request->boolean('preview'),
        ]);
    }
}
