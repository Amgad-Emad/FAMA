<?php

namespace App\Services;

use App\Models\SoftwareStack;
use App\Models\Talent;
use App\Support\PracticeFields;
use Carbon\CarbonImmutable;

/**
 * "About your practice" (ADR-AG) — the one write path for the per-skill custom
 * fields, and the one place their values are assembled for the editor and the
 * public profile. Stored fields live in talents.practice; `software` mirrors the
 * Software list (software_stack) and `digitals` reads the Digitals content, so
 * neither is ever stored twice.
 */
class PracticeService extends Service
{
    /**
     * Every value the editor and the profile need: stored + derived. Callers
     * eager-load `softwareStack` and `digitals` (no per-request queries).
     *
     * @return array<string, mixed>
     */
    public function values(Talent $talent): array
    {
        $values = (array) ($talent->practice ?? []);

        $software = $talent->softwareStack->pluck('software_name')
            ->filter(fn ($name) => in_array($name, PracticeFields::SOFTWARE, true))
            ->values()->all();
        if ($software !== []) {
            $values['software'] = $software;
        }

        $taken = $talent->digitals->map(fn ($d) => $d->captured_at ?? $d->created_at)->filter()->max();
        if ($taken) {
            $values['digitals'] = ['d' => CarbonImmutable::parse($taken)->toDateString()];
        }

        return $values;
    }

    /**
     * Replace the talent's practice values with the given set (the editor sends
     * the whole draft). Unknown fields and malformed values are dropped; a
     * missing field is cleared. `software` syncs the Software list: options
     * picked here are added, options un-picked here are removed, and any other
     * software the talent listed by hand is left alone.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed> the values after saving
     */
    public function update(Talent $talent, array $fields): array
    {
        return $this->runInTransaction(function () use ($talent, $fields): array {
            $talent->practice = PracticeFields::clean($fields) ?: null;
            $talent->save();

            $picked = array_values(array_intersect(PracticeFields::SOFTWARE, (array) ($fields['software'] ?? [])));
            $existing = $talent->softwareStack()->pluck('software_name')->all();

            $talent->softwareStack()
                ->whereIn('software_name', array_diff(PracticeFields::SOFTWARE, $picked))
                ->get()->each->delete();

            $position = (int) $talent->softwareStack()->max('position');
            foreach (array_diff($picked, $existing) as $name) {
                $talent->softwareStack()->create(['software_name' => $name, 'position' => ++$position]);
            }

            $talent->load(['softwareStack', 'digitals']);

            return $this->values($talent);
        }, ['talent_id' => $talent->id]);
    }
}
