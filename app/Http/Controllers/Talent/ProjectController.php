<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreProjectRequest;
use App\Http\Requests\Talent\UpdateProjectCoverRequest;
use App\Http\Requests\Talent\UpdateProjectRequest;
use App\Http\Resources\TalentProjectResource;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\TalentProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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
     * The Projects page shell: the talent's skills (for the filter bar and the
     * composer's required skill picker) and the counts per skill.
     */
    public function index(): View
    {
        $talent = $this->talent()->load('talentTypes');
        $skills = $this->orderedSkills($talent);

        return view('talent.projects', [
            'talent' => $talent,
            'skills' => $skills->map(fn (TalentType $type) => [
                'id' => (int) $type->id,
                'slug' => $type->slug,
                'name' => $type->getTranslation('name', app()->getLocale(), false) ?: $type->getTranslation('name', 'en', false),
                'icon' => $type->icon,
                'is_primary' => (bool) $type->pivot->is_primary,
            ])->all(),
            'total' => $talent->projects()->count(),
        ]);
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
            ->with(['media', 'talentTypes'])
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

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $project = $this->projects->create(
            $this->talent(),
            collect($data)->except('skill_ids')->all(),
            array_map('intval', $data['skill_ids']),
        );

        return response()->success(
            $this->present($project),
            __('Project added.'),
            meta: ['counts' => $this->counts()],
            status: 201,
        );
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->ensureOwns($project);

        $data = $request->validated();
        $project = $this->projects->update(
            $project,
            collect($data)->except('skill_ids')->all(),
            array_map('intval', $data['skill_ids']),
        );

        return response()->success(
            $this->present($project),
            __('Project saved.'),
            meta: ['counts' => $this->counts()],
        );
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->ensureOwns($project);
        $this->projects->delete($project);

        return response()->success(null, __('Project removed.'), meta: ['counts' => $this->counts()]);
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->projects->reorder($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    public function uploadCover(UpdateProjectCoverRequest $request, Project $project): JsonResponse
    {
        $this->ensureOwns($project);
        $project = $this->projects->setCover($project, $request->file('cover'));

        return response()->success($this->present($project), __('Cover updated.'));
    }

    public function destroyCover(Project $project): JsonResponse
    {
        $this->ensureOwns($project);
        $project = $this->projects->removeCover($project);

        return response()->success($this->present($project), __('Cover removed.'));
    }

    // ----- Internal ----------------------------------------------------------

    /**
     * A single project payload with the relations the resource needs loaded.
     *
     * @return array<string, mixed>
     */
    private function present(Project $project): array
    {
        return (new TalentProjectResource($project->load(['media', 'talentTypes'])))->resolve();
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
