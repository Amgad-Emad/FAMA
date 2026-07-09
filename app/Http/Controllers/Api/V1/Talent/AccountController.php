<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\UpdateAccountRequest;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Talent · Account
 *
 * @authenticated
 *
 * Account settings — slug, preferences (`meta`), and the publish/unpublish toggle
 * (TalentProfile state machine).
 */
class AccountController extends TalentApiController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    /**
     * Get my account
     */
    public function show(): JsonResponse
    {
        $talent = $this->talent();

        return response()->success([
            'slug' => $talent->slug,
            'meta' => $talent->meta,
            'is_published' => (bool) $talent->is_published,
            'status' => $talent->status->getValue(),
        ]);
    }

    /**
     * Update my account
     */
    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $talent = $this->profile->updateCore($this->talent(), $request->only('slug'));

        if ($request->has('meta')) {
            $talent->forceFill(['meta' => $request->input('meta')])->save();
        }

        return response()->success(['slug' => $talent->slug, 'meta' => $talent->meta], __('Account updated.'));
    }

    /**
     * Publish / unpublish my profile
     *
     * @bodyParam publish boolean required Whether to publish (true) or unpublish (false). Example: true
     */
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
