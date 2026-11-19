<?php

namespace App\Services;

use App\Models\Talent;
use App\Support\Experience;

/**
 * Experience roles (ADR-AK) — the one write path. The editor sends the whole
 * list (it is part of the Profile structure draft); rows not in it are deleted
 * through Eloquent (their logo media goes with them), the rest are updated or
 * created in list order.
 */
class ExperienceService extends Service
{
    /**
     * Replace the talent's roles with the given list.
     *
     * @param  list<array<string, mixed>>  $roles  validated role payloads
     * @return list<array<string, mixed>> the roles as saved (editor shape)
     */
    public function replace(Talent $talent, array $roles): array
    {
        return $this->runInTransaction(function () use ($talent, $roles): array {
            $keep = collect($roles)->pluck('id')->filter()->map('intval')->all();
            $talent->brandCollabs()->whereNotIn('id', $keep)->get()->each->delete();

            foreach (array_values($roles) as $position => $r) {
                $cur = (bool) ($r['cur'] ?? false);
                $attributes = [
                    'role_title' => trim((string) $r['t']),
                    'brand_name' => trim((string) $r['n']),
                    'employment_type' => ($r['et'] ?? '') ?: null,
                    'location' => trim((string) ($r['loc'] ?? '')) ?: null,
                    'location_type' => ($r['lt'] ?? '') ?: null,
                    'is_current' => $cur,
                    'start_month' => ($r['sm'] ?? 0) ?: null,
                    'start_year' => (int) $r['sy'],
                    'end_month' => $cur ? null : (($r['em'] ?? 0) ?: null),
                    'end_year' => $cur ? null : (($r['ey'] ?? 0) ?: null),
                    'highlights' => trim((string) ($r['hl'] ?? '')) ?: null,
                    'skill_ids' => array_values(array_map('intval', (array) ($r['sk'] ?? []))),
                    'position' => $position,
                ];

                $row = ! empty($r['id']) ? $talent->brandCollabs()->find($r['id']) : null;
                $row ? $row->update($attributes) : $talent->brandCollabs()->create($attributes);
            }

            return $talent->brandCollabs()->orderBy('position')->get()->map(fn ($row) => Experience::role($row))->all();
        }, ['talent_id' => $talent->id]);
    }
}
