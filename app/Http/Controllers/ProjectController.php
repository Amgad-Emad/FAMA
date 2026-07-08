<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Talent;
use Illuminate\View\View;

/**
 * Project detail — `fama.com/{slug}/work/{project}` (talent-spec, public). One
 * projects record expanded. 404 unless the talent is published and the project
 * belongs to them.
 */
class ProjectController extends Controller
{
    public function show(string $slug, Project $project): View
    {
        $talent = Talent::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        abort_unless((int) $project->talent_id === (int) $talent->getKey(), 404);

        $project->load('media');

        return view('public.project', ['talent' => $talent, 'project' => $project]);
    }
}
