<?php

namespace App\Http\Controllers\Brand;

use App\Http\Resources\TalentCardResource;
use App\Models\Talent;
use App\Models\TalentType;
use App\Queries\BrandTalentFeed;
use App\Services\BrandSignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Brand discovery feed (brand-spec) — the personalised matching layer. The feed
 * is paginated via Ajax (BrandTalentFeed); save/brief actions write signals that
 * enrich the preference profile.
 */
class DiscoveryController extends BrandController
{
    public function __construct(
        private readonly BrandTalentFeed $feed,
        private readonly BrandSignalService $signals,
    ) {}

    public function index(): View
    {
        return view('brand.discover', ['types' => TalentType::orderBy('id')->get()]);
    }

    public function feed(): JsonResponse
    {
        $paginator = $this->feed->paginate($this->brand());

        return response()->paginated($paginator, TalentCardResource::collection($paginator->getCollection()));
    }

    public function save(Request $request): JsonResponse
    {
        $this->signals->save($this->brand(), $this->talentFrom($request));

        return response()->success(null, __('Saved.'));
    }

    public function brief(Request $request): JsonResponse
    {
        $this->signals->brief($this->brand(), $this->talentFrom($request));

        return response()->success(null, __('Brief sent.'));
    }

    private function talentFrom(Request $request): Talent
    {
        $data = $request->validate(['talent_id' => ['required', 'integer', 'exists:talents,id']]);

        return Talent::findOrFail($data['talent_id']);
    }
}
