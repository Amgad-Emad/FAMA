<?php

namespace App\Http\Controllers\Talent;

use App\Models\BrandProject;
use App\Models\ContractFlow;
use App\Services\ContractService;
use App\Support\Html\BriefSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Talent applications to brand projects (the public project "Apply" CTA). A talent
 * writes a rich-text brief (why they're a fit, @-mentioning their own projects) and
 * attaches files; that becomes the opening message of the talent↔brand contract scoped
 * to the project. Everything delegates to ContractService; the brief HTML is sanitized
 * to a strict allowlist before it is ever stored or rendered.
 */
class ApplicationController extends TalentController
{
    public function __construct(private readonly ContractService $contracts) {}

    /**
     * The talent's own projects, for the @-mention picker (id + title).
     */
    public function mentions(Request $request): JsonResponse
    {
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $locale = app()->getLocale();

        // A talent has few portfolio projects — filter the localized title in PHP
        // (case-insensitively) to sidestep MySQL's case-sensitive JSON collation.
        $projects = $this->talent()->projects()->orderBy('position')->get()
            ->map(fn ($project) => ['id' => $project->id, 'title' => $project->getTranslation('title', $locale)])
            ->filter(fn ($project) => $q === '' || str_contains(mb_strtolower((string) $project['title']), $q))
            ->take(8)
            ->values();

        return response()->success(['projects' => $projects]);
    }

    /**
     * Submit an application to a public, open project.
     */
    public function store(Request $request, BrandProject $brandProject): JsonResponse
    {
        // Only open, public projects on a published brand can be applied to.
        $brandProject->loadMissing('brand');
        abort_unless(
            (bool) $brandProject->is_public
            && in_array((string) $brandProject->status, ['open', 'in_progress'], true)
            && (bool) $brandProject->brand?->is_published,
            404,
        );

        $data = $request->validate([
            'brief' => ['required', 'string', 'max:20000'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,zip'],
        ]);

        $brief = BriefSanitizer::clean($data['brief']);
        if (trim(strip_tags($brief)) === '' && ! str_contains($brief, 'class="mention"')) {
            throw ValidationException::withMessages(['brief' => __('Write a short brief before applying.')]);
        }

        $flow = ContractFlow::where('is_default', true)->first() ?? ContractFlow::query()->firstOrFail();

        $contract = $this->contracts->applyToProject(
            $brandProject,
            $this->talent(),
            $brief,
            array_values($request->file('attachments', [])),
            $flow,
        );

        return response()->success(
            ['contract_url' => route('talent.contracts.show', $contract)],
            __('Application sent.'),
            status: 201,
        );
    }
}
