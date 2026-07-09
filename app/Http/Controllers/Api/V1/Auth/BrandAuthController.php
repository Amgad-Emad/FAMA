<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\Api\V1\Auth\RegisterBrandRequest;
use App\Http\Resources\Api\V1\BrandResource;
use App\Models\Brand;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @group Brand authentication
 *
 * Token auth for the `brand` guard: public sign-up plus the shared
 * login/logout/refresh/me flow. Issued tokens carry the `brand` ability.
 */
class BrandAuthController extends AbstractAuthController
{
    protected function guard(): string
    {
        return 'brand';
    }

    protected function model(): string
    {
        return Brand::class;
    }

    protected function resource(Model $entity): JsonResource
    {
        return new BrandResource($entity);
    }

    /**
     * Register a brand
     *
     * Create a new brand account and return an ability-scoped token. The account
     * starts incomplete (onboarding gates the discovery feed) and unpublished.
     *
     * @unauthenticated
     *
     * @response 201 scenario="Created" {"success":true,"data":{"token":"1|xxxx","token_type":"Bearer","abilities":["brand"],"brand":{"id":1,"slug":"nomad-coffee-ab12cd","name":"Nomad Coffee"}},"message":null,"errors":null,"meta":null}
     */
    public function register(RegisterBrandRequest $request): JsonResponse
    {
        $name = (string) $request->validated('name');

        $brand = Brand::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        event(new Registered($brand));

        return $this->respondWithToken($brand, $request->deviceName(), 201);
    }
}
