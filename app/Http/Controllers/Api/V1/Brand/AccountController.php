<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\States\Brand\Published;
use App\States\Brand\Unpublished;
use App\Support\Brand\BrandOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * @group Brand · Account
 *
 * @authenticated
 *
 * Settings-stage fields (slug, founded year, company size, website, phone) and
 * the publish toggle (the Brand status machine's published ⇄ unpublished;
 * `is_published` is a synced projection). Publishing requires a complete profile.
 */
class AccountController extends BrandApiController
{
    /**
     * Get my account
     */
    public function show(): JsonResponse
    {
        $brand = $this->brand();

        return response()->success([
            'slug' => $brand->slug,
            'founded_year' => $brand->founded_year,
            'company_size' => $brand->company_size,
            'website' => $brand->website,
            'phone' => $brand->phone,
            'is_complete' => (bool) $brand->is_complete,
            'is_published' => (bool) $brand->is_published,
            'status' => $brand->status->getValue(),
        ]);
    }

    /**
     * Update my account
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($this->brand()->getKey())],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'company_size' => ['nullable', Rule::in(BrandOptions::COMPANY_SIZES)],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $this->brand()->update($data);

        return response()->success(['slug' => $this->brand()->slug], __('Account updated.'));
    }

    /**
     * Publish / unpublish my profile
     *
     * @bodyParam publish boolean required Whether to publish (true) or unpublish (false). Example: true
     */
    public function publish(Request $request): JsonResponse
    {
        $brand = $this->brand();
        $publish = $request->boolean('publish');

        if ($publish && ! $brand->is_complete) {
            throw new InvalidArgumentException(__('Finish onboarding before publishing.'));
        }

        $current = $brand->status->getValue();
        if ($publish && $current !== 'published') {
            $brand->status->transitionTo(Published::class);
        } elseif (! $publish && $current === 'published') {
            $brand->status->transitionTo(Unpublished::class);
        }

        return response()->success(
            ['is_published' => (bool) $brand->fresh()->is_published],
            $publish ? __('Profile published.') : __('Profile unpublished.'),
        );
    }
}
