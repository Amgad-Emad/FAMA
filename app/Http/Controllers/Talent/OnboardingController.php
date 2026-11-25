<?php

namespace App\Http\Controllers\Talent;

use App\Models\Project;
use App\Models\Talent;
use App\Services\ProfileCompletionService;
use App\Services\SkillsService;
use App\Support\Locations;
use App\Support\ProjectTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The guided onboarding (GET /talent/welcome/{step?}, ADR-AR) — five screens
 * that walk a new talent to a COMPLETE portfolio: You · What you do · Your work
 * · Your story · Go Live. It owns no writes: every screen saves through the
 * Profile / Skills / Projects endpoints that already exist, and re-reads the
 * path from GET /talent/profile/state. The step map is
 * ProfileCompletionService::ONBOARDING (THE home); this controller only renders.
 */
class OnboardingController extends TalentController
{
    /** How many projects the Work screen shows (three is the goal; a few more fit). */
    private const PROJECT_PREVIEW = 6;

    public function __construct(
        private readonly ProfileCompletionService $completion,
        private readonly SkillsService $skills,
    ) {}

    /**
     * Render the flow, opened on `$step` — or, without one, on the screen the
     * talent should resume on. An unknown step redirects to the resume screen
     * (an old bookmark never 404s a new talent).
     */
    public function show(?string $step = null): View|RedirectResponse
    {
        $talent = $this->talent()->load(['media', 'socialHandles', 'specialities']);
        $state = $this->completion->state($talent);

        if ($step !== null && ! array_key_exists($step, ProfileCompletionService::ONBOARDING)) {
            return redirect()->route('talent.onboarding');
        }

        return view('talent.onboarding', [
            'talent' => $talent,
            'step' => $step ?? $state['onboarding_step'],
            'state' => $state,
            'screens' => ProfileCompletionService::ONBOARDING,
            'skills' => $this->skills->editorPayload($talent),
            'projects' => $this->projects($talent),
            'projectCount' => $talent->projects()->count(),
            'social' => $talent->socialHandles->map(fn ($h) => [
                'platform' => $h->platform, 'handle' => $h->handle, 'url' => $h->url,
            ])->values()->all(),
            'countries' => Locations::countries(),
            // The live preview mirrors the public header (availability chips, the featured cover).
            'preview' => [
                'is_available' => (bool) $talent->is_available,
                'will_travel' => (bool) $talent->will_travel,
                'featured_id' => $talent->featured_project_id ? (int) $talent->featured_project_id : null,
            ],
        ]);
    }

    /**
     * The talent's first few projects for the Work screen and the preview card:
     * id, display title, lead image and edit link. Media eager-loaded (the
     * lead-image accessor reads it — preventLazyLoading would not catch it).
     *
     * @return list<array<string, mixed>>
     */
    private function projects(Talent $talent): array
    {
        return $talent->projects()
            ->with('media')
            ->orderBy('position')->orderBy('id')
            ->limit(self::PROJECT_PREVIEW)
            ->get()
            ->map(fn (Project $project) => [
                'id' => (int) $project->id,
                'title' => ProjectTitle::forProject($project)['name'] ?: __('Untitled project'),
                'image' => $project->lead_image_url,
                'edit' => route('talent.projects.edit', ['project' => $project, 'from' => 'welcome']),
            ])->values()->all();
    }
}
