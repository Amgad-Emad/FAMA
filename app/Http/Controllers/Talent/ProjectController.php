<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreProjectMediaRequest;
use App\Http\Requests\Talent\StoreProjectRequest;
use App\Http\Requests\Talent\UpdateProjectCoverRequest;
use App\Http\Requests\Talent\UpdateProjectRequest;
use App\Http\Resources\TalentProjectResource;
use App\Models\Project;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\TalentProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Projects (talent-spec §"Selected work") — the talent's own portfolio of work,
 * on its own page rather than as one generic content table.
 *
 * Every project carries at least one skill (ADR-X): the tags decide which skill
 * tabs it appears in on the public profile, and tag 0 is the home tab mirrored
 * onto `projects.talent_type_id` (ADR-Q). The page is a Blade shell; everything
 * else is the JSON envelope for the Alpine/http.js editor (no reloads). All
 * writes go through TalentProjectService.
 */
class ProjectController extends TalentController
{
    public function __construct(private readonly TalentProjectService $projects) {}

    /**
     * The Projects page shell (fama-ui `projects.html`): the talent's skills for
     * the filter chips, and the total for the "All work" count. The list itself
     * is Ajax (data()); creating and editing live on their own pages now.
     */
    public function index(): View
    {
        $talent = $this->talent()->load('talentTypes');

        return view('talent.projects', [
            'talent' => $talent,
            'skills' => $this->presentSkills($this->orderedSkills($talent)),
            'total' => $talent->projects()->count(),
        ]);
    }

    /**
     * The project editor on its own page (fama-ui `project-edit.html`) for a NEW
     * project. Nothing is written until the talent publishes; the page then posts
     * to store() and carries on editing the created project in place.
     */
    public function create(): View
    {
        return view('talent.project-edit', $this->editorData(null));
    }

    /**
     * The project editor for an existing project. ANY owner may open it — the
     * creator or an accepted co-owner (ProjectPolicy::update); 403 otherwise.
     */
    public function edit(Project $project): View
    {
        $this->authorizeTalent('update', $project);

        return view('talent.project-edit', $this->editorData($project));
    }

    /**
     * Paginated project list, newest ordering first (the talent's own order),
     * optionally filtered to one skill (`?skill=<id>`) or to the projects that
     * carry no skill yet (`?skill=none` — left behind when a skill is removed).
     */
    public function data(Request $request): JsonResponse
    {
        $skill = (string) $request->query('skill', '');

        $query = $this->talent()->projects()
            ->with(['media', 'talentTypes', 'specialities', 'credits.talent', 'credits.talentType', 'ownerCredits'])
            ->orderBy('position')
            ->orderBy('id');

        if ($skill === 'none') {
            $query->withoutSkill();
        } elseif ($skill !== '' && ctype_digit($skill)) {
            $query->inSkillScope((int) $skill);
        }

        $paginator = $query->paginate(12)->withQueryString();

        return response()->paginated(
            $paginator,
            TalentProjectResource::collection($paginator->getCollection())->resolve(),
            meta: ['counts' => $this->counts()],
        );
    }

    /**
     * Create a project (the editor page's Publish). Skills are required and
     * must be the talent's own (ADR-X); the service re-checks both rules.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $project = $this->projects->create(
            $this->talent(),
            collect($data)->except(['skill_ids', 'speciality_ids'])->all(),
            array_map('intval', $data['skill_ids']),
            array_map('intval', $data['speciality_ids'] ?? []),
        );

        return response()->success(
            $this->present($project),
            __('Project added.'),
            meta: ['counts' => $this->counts()],
            status: 201,
        );
    }

    /**
     * Save the whole project (the editor autosaves through this). Absent
     * `speciality_ids` leaves the picks alone; an empty list clears them.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);

        $data = $request->validated();
        $project = $this->projects->update(
            $project,
            collect($data)->except(['skill_ids', 'speciality_ids'])->all(),
            array_map('intval', $data['skill_ids']),
            // Absent means "leave the picks alone"; present-but-empty clears them.
            array_key_exists('speciality_ids', $data) ? array_map('intval', $data['speciality_ids']) : null,
        );

        return response()->success(
            $this->present($project),
            __('Project saved.'),
            meta: ['counts' => $this->counts()],
        );
    }

    /**
     * Delete one of the talent's own projects, with its tags and media.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeTalent('delete', $project);
        $this->projects->delete($project);

        return response()->success(null, __('Project removed.'), meta: ['counts' => $this->counts()]);
    }

    /**
     * Leave a project (co-ownership). A co-owner's credit is withdrawn; the
     * creator hands the project to the longest-standing co-owner; a sole owner
     * is told to delete instead (422). The rules live in
     * TalentProjectService::leave().
     */
    public function leave(Project $project): JsonResponse
    {
        $this->authorizeTalent('leave', $project);

        $wasHome = (int) $project->talent_id === (int) $this->talent()->getKey();
        $project = $this->projects->leave($project, $this->talent())->loadMissing('talent');

        return response()->success(
            ['id' => (int) $project->id, 'owner' => $wasHome ? $project->talent?->display_name : null],
            $wasHome
                ? __(':name owns it now.', ['name' => (string) $project->talent?->display_name])
                : __('You left the project.'),
            meta: ['counts' => $this->counts()],
        );
    }

    /**
     * Persist the order visitors see (the unfiltered list's drag order).
     */
    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->projects->reorder($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    /**
     * Upload (or replace) the project's single-file cover.
     */
    public function uploadCover(UpdateProjectCoverRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);
        $project = $this->projects->setCover($project, $request->file('cover'));

        return response()->success($this->present($project), __('Cover updated.'));
    }

    /**
     * Remove the cover; the lead media item takes its place on cards.
     */
    public function destroyCover(Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);
        $project = $this->projects->removeCover($project);

        return response()->success($this->present($project), __('Cover removed.'));
    }

    // ----- Internal ----------------------------------------------------------

    /**
     * Upload work into the project's media gallery (ADR-AA).
     */
    public function storeMedia(StoreProjectMediaRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);

        $project = $this->projects->addMedia($project, $request->file('files', []));

        return response()->success($this->present($project), __('Work added.'));
    }

    /**
     * Reorder the work. The order IS the edit: the first frame is doing the most
     * work, so it is chosen rather than defaulted.
     */
    public function reorderMedia(ReorderRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTalent('update', $project);

        $project = $this->projects->reorderMedia($project, $request->orderedIds());

        return response()->success($this->present($project), __('Order saved.'));
    }

    /**
     * Remove one uploaded file from the project's gallery.
     */
    public function destroyMedia(Project $project, int $media): JsonResponse
    {
        $this->authorizeTalent('update', $project);

        $project = $this->projects->removeMedia($project, $media);

        return response()->success($this->present($project), __('Removed.'));
    }

    /**
     * A single project payload with the relations the resource needs loaded.
     *
     * @return array<string, mixed>
     */
    private function present(Project $project): array
    {
        return (new TalentProjectResource($project->load([
            'media', 'talentTypes', 'specialities', 'credits.talent', 'credits.talentType', 'ownerCredits',
        ])))->resolve();
    }

    /**
     * How many projects sit under each skill (a project tagged twice counts in
     * both), plus the "all" and "no skill" totals the filter bar shows.
     *
     * TWO flat queries, never one per skill: the home column and the tag rows are
     * pulled as (project, skill) pairs and unioned in PHP — the same union
     * Project::skillIds() applies, so the counts always match what the tabs and
     * the filtered list actually show.
     *
     * @return array{all: int, none: int, by_skill: array<int, int>}
     */
    private function counts(): array
    {
        $talentId = (int) $this->talent()->getKey();

        $homes = DB::table('projects')
            ->where('talent_id', $talentId)
            ->select('id', 'talent_type_id')
            ->get();

        $tags = DB::table('project_talent_type')
            ->join('projects', 'projects.id', '=', 'project_talent_type.project_id')
            ->where('projects.talent_id', $talentId)
            ->select('project_talent_type.project_id as id', 'project_talent_type.talent_type_id')
            ->get();

        /** @var array<int, array<int, true>> $bySkill skill id => set of project ids */
        $bySkill = [];
        $scoped = [];

        foreach ([...$homes, ...$tags] as $pair) {
            if ($pair->talent_type_id === null) {
                continue;
            }

            $bySkill[(int) $pair->talent_type_id][(int) $pair->id] = true;
            $scoped[(int) $pair->id] = true;
        }

        return [
            'all' => $homes->count(),
            'none' => $homes->reject(fn ($p) => isset($scoped[(int) $p->id]))->count(),
            'by_skill' => array_map('count', $bySkill),
        ];
    }

    /**
     * Everything the editor page needs: the talent's skills (the required skill
     * picker), the speciality vocabulary for those skills (ADR-Y, ONE query,
     * keyed by skill id), every skill for a collaborator credit (ADR-AA — a
     * photographer crediting a stylist needs Styling even though they do not do
     * it), and the project payload when editing.
     *
     * Co-ownership: when editing, the skill picker offers the project's HOME
     * owner's skills (the tags must be theirs, ADR-X), even if a co-owner is
     * the one editing; `homeOwner` drives the preview URL.
     *
     * @return array<string, mixed>
     */
    private function editorData(?Project $project): array
    {
        $talent = $this->talent()->load('talentTypes');
        $homeOwner = $project ? $project->talent()->with('talentTypes')->firstOrFail() : $talent;
        $skills = $this->orderedSkills($homeOwner);

        $specialities = Speciality::query()
            ->selectable()
            ->whereIn('talent_type_id', $skills->pluck('id'))
            ->orderBy('position')->orderBy('id')
            ->get();

        return [
            'talent' => $talent,
            'homeOwner' => $homeOwner,
            'project' => $project,
            'payload' => $project ? $this->present($project) : null,
            'skills' => $this->presentSkills($skills),
            'allSkills' => TalentType::query()->orderBy('id')->get()->map(fn (TalentType $type) => [
                'id' => (int) $type->id,
                'name' => $this->localName($type),
            ])->values()->all(),
            'specialities' => $specialities->groupBy('talent_type_id')->map(
                fn (Collection $group) => $group->map(fn (Speciality $speciality) => [
                    'id' => (int) $speciality->id,
                    'slug' => $speciality->slug,
                    'name' => $this->localName($speciality),
                    'is_regional' => (bool) $speciality->is_regional,
                ])->values()
            ),
        ];
    }

    /**
     * The talent's skills as the pages consume them (id, slug, name, icon,
     * primary flag), in the order given.
     *
     * @param  Collection<int, TalentType>  $skills
     * @return array<int, array<string, mixed>>
     */
    private function presentSkills(Collection $skills): array
    {
        return $skills->map(fn (TalentType $type) => [
            'id' => (int) $type->id,
            'slug' => $type->slug,
            'name' => $this->localName($type),
            'icon' => $type->icon,
            'is_primary' => (bool) $type->pivot->is_primary,
        ])->values()->all();
    }

    /**
     * A translatable `name` in the current locale, falling back to English.
     */
    private function localName(TalentType|Speciality $model): string
    {
        return (string) ($model->getTranslation('name', app()->getLocale(), false) ?: $model->getTranslation('name', 'en', false));
    }

    /**
     * The talent's skills primary-first, then by pivot position (ADR-Q) — the
     * same order the profile editor and the public tab bar use.
     *
     * @return Collection<int, TalentType>
     */
    private function orderedSkills(Talent $talent): Collection
    {
        return $talent->talentTypes
            ->sortBy(fn (TalentType $type) => [$type->pivot->is_primary ? 0 : 1, (int) $type->pivot->position])
            ->values();
    }
}
