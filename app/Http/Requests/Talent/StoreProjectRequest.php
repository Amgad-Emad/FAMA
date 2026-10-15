<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a portfolio project. `skill_ids` is REQUIRED with at least one entry,
 * each one of the talent's own skills (ADR-X) — the same rule the service
 * enforces, surfaced here as a field error the editor can show inline.
 * `speciality_ids` is optional and must resolve under those skills (ADR-Y).
 */
class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the talent guard already gates the route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ownSkills = $this->taggableSkillIds();

        return [
            'skill_ids' => ['required', 'array', 'min:1'],
            'skill_ids.*' => ['integer', Rule::in($ownSkills)],

            // Optional, but every pick must hang off a tagged skill — the
            // service is what proves that (ADR-Y); this is the shape check.
            'speciality_ids' => ['sometimes', 'array'],
            'speciality_ids.*' => ['integer', 'exists:specialities,id'],

            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'title.ar' => ['nullable', 'string', 'max:255'],

            'role' => ['nullable', 'array'],
            'role.en' => ['nullable', 'string', 'max:255'],
            'role.ar' => ['nullable', 'string', 'max:255'],

            'summary' => ['nullable', 'array'],
            'summary.en' => ['nullable', 'string', 'max:5000'],
            'summary.ar' => ['nullable', 'string', 'max:5000'],

            'body' => ['nullable', 'array'],
            'body.en' => ['nullable', 'string', 'max:20000'],
            'body.ar' => ['nullable', 'string', 'max:20000'],

            'client_name' => ['nullable', 'string', 'max:255'],
            // Recorded, not displayed (ADR-AA). `year` is a derived projection
            // of this and is no longer accepted from a client at all.
            'occurred_on' => ['nullable', 'date', 'before_or_equal:'.now()->addYear()->toDateString()],
            'url' => ['nullable', 'url', 'max:2048'],

            // Up to four results, each {label, value, compare} — the value's
            // shape decides how it renders (number / percent bar / rise-fall).
            // Put this project on the profile cover (true only; "un-featuring"
            // happens by featuring another — FeaturedProjectService).
            'featured' => ['sometimes', 'boolean'],

            'results' => ['nullable', 'array', 'max:4'],
            'results.*.label' => ['nullable', 'string', 'max:60'],
            'results.*.value' => ['nullable', 'string', 'max:60'],
            'results.*.compare' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * The skills a project may be tagged with: the requesting talent's own on
     * create (they become its home owner, ADR-X).
     *
     * @return list<int>
     */
    protected function taggableSkillIds(): array
    {
        return $this->user('talent')?->talentTypes()->pluck('talent_types.id')->map('intval')->all() ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'skill_ids.required' => __('Pick at least one skill for this project.'),
            'skill_ids.min' => __('Pick at least one skill for this project.'),
            'skill_ids.*.in' => __('You can only tag your own skills on a project.'),
            'title.en.required' => __('Give the project a title.'),
            'results.max' => __('A project can show up to four results.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['skill_ids' => __('skills'), 'title.en' => __('title')];
    }
}
