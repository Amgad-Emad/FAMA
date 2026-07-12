<?php

namespace App\Http\Controllers\Brand;

use App\States\Brand\Published;
use App\States\Brand\Unpublished;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Brand account / settings (brand-spec) — settings-stage fields, slug, and the
 * publish toggle (the status machine's published ⇄ unpublished; is_published is
 * a synced projection).
 */
class AccountController extends BrandController
{
    public function index(): View
    {
        return view('brand.account', ['brand' => $this->brand()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($this->brand()->getKey())],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'company_size' => ['nullable', Rule::in(['solo', 'small', 'medium', 'large', 'enterprise'])],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $this->brand()->update($data);

        return response()->success(['slug' => $this->brand()->slug], __('Account updated.'));
    }

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
