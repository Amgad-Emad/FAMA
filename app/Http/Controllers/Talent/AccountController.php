<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\UpdateAccountRequest;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Account / settings (talent-spec) — slug, publish/unpublish toggle, prefs.
 */
class AccountController extends TalentController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    public function index(): View
    {
        return view('talent.account', ['talent' => $this->talent()]);
    }

    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $talent = $this->profile->updateCore($this->talent(), $request->only('slug'));

        if ($request->has('meta')) {
            $talent->forceFill(['meta' => $request->input('meta')])->save();
        }

        return response()->success(['slug' => $talent->slug, 'meta' => $talent->meta], __('Account updated.'));
    }

    public function publish(Request $request): JsonResponse
    {
        $publish = $request->boolean('publish');

        $talent = $publish
            ? $this->profile->publish($this->talent())
            : $this->profile->unpublish($this->talent());

        return response()->success([
            'is_published' => (bool) $talent->is_published,
            'status' => $talent->status->getValue(),
        ], $publish ? __('Profile published.') : __('Profile unpublished.'));
    }
}
