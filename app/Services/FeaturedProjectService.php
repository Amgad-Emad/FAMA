<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Talent;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The featured project — the one on the public profile's cover — is the
 * talent's own pick (`talents.featured_project_id`), from the projects they
 * HOME-own. The rule: a talent with projects always has exactly one featured.
 * It is changed by featuring another project, never switched off, so a
 * talent's only project is featured and stays featured.
 *
 * ensure() repairs the pick after anything that can invalidate it (the first
 * project is created, the featured one is deleted or handed to a co-owner).
 */
class FeaturedProjectService extends Service
{
    /** Feature one of the talent's own projects (replaces the previous pick). */
    public function feature(Talent $talent, Project $project): void
    {
        if ((int) $project->talent_id !== (int) $talent->getKey()) {
            throw new InvalidArgumentException(__('You can only feature a project you own.'));
        }

        $talent->forceFill(['featured_project_id' => $project->getKey()])->save();
    }

    /**
     * Make sure the pick points at one of the talent's projects: keep a valid
     * pick, otherwise fall back to the automatic one (or null with no projects).
     */
    public function ensure(Talent $talent): void
    {
        $owned = $talent->projects()->with('media')->orderBy('position')->orderBy('id')->get();
        $id = $this->resolve($talent, $owned)?->getKey();

        if ($id !== $talent->featured_project_id) {
            $talent->forceFill(['featured_project_id' => $id])->save();
        }
    }

    /**
     * The featured project among the talent's own projects: their pick when it
     * is still theirs, else the first with a picture, else the first.
     *
     * @param  Collection<int, Project>  $owned  in display order, media loaded
     */
    public function resolve(Talent $talent, Collection $owned): ?Project
    {
        return $owned->firstWhere('id', $talent->featured_project_id)
            ?? $owned->first(fn (Project $p) => filled($p->lead_image_url))
            ?? $owned->first();
    }

    /** The featured project's id for a talent (null with no projects). */
    public function featuredId(Talent $talent): ?int
    {
        $owned = $talent->projects()->with('media')->orderBy('position')->orderBy('id')->get();

        return $this->resolve($talent, $owned)?->getKey();
    }
}
