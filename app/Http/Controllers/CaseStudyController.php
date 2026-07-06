<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use App\Models\Talent;
use Illuminate\View\View;

/**
 * Project / case-study detail — `fama.com/{slug}/work/{caseStudy}` (talent-spec,
 * public). One case_studies record expanded. 404 unless the talent is published
 * and the case study belongs to them.
 */
class CaseStudyController extends Controller
{
    public function show(string $slug, CaseStudy $caseStudy): View
    {
        $talent = Talent::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        abort_unless((int) $caseStudy->talent_id === (int) $talent->getKey(), 404);

        $caseStudy->load('media');

        return view('public.case-study', ['talent' => $talent, 'study' => $caseStudy]);
    }
}
