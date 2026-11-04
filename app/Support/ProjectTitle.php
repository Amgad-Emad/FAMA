<?php

namespace App\Support;

use App\Models\Project;

/**
 * A project title as the fama-ui pages show it (prototype featured.js `split`).
 *
 * "Desert Light — 2026" is two things: the name is the headline and the year
 * becomes a small tag on the picture; any other second part ("Nomad — Autumn
 * launch") stays a sub-line under the name. ONE home for this split — the
 * public profile grid, the project page and its "more projects" all use it.
 *
 * The year tag (2026-11-04, co-founder prototype, supersedes ADR-AA's "date
 * never shown"): a year typed into the title wins, else the project's recorded
 * year (the projection of `occurred_on`) — the editor's Date field "sorts your
 * projects, and shows as the year tag".
 */
final class ProjectTitle
{
    /**
     * Split a title into its name, an optional sub-line and a trailing
     * four-digit year ("" when absent).
     *
     * @return array{name: string, sub: string, year: string}
     */
    public static function split(?string $title): array
    {
        $title = (string) $title;
        $parts = preg_split('/\s+[\x{2014}\x{2013}-]\s+/u', $title) ?: [$title];
        $name = (string) array_shift($parts);
        $rest = implode(' — ', $parts);
        $year = preg_match('/^(19|20)\d\d$/', $rest) ? $rest : '';

        return ['name' => $name, 'sub' => $year === '' ? $rest : '', 'year' => $year];
    }

    /**
     * The split for a project, with the year tag falling back to the
     * project's recorded year when the title carries none.
     *
     * @return array{name: string, sub: string, year: string}
     */
    public static function forProject(Project $project): array
    {
        $split = self::split($project->title);
        if ($split['year'] === '' && $project->year) {
            $split['year'] = (string) $project->year;
        }

        return $split;
    }
}
