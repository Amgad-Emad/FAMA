<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginApiRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;

/**
 * Shared Sanctum token auth for Fama's three login entities. Each concrete
 * controller (talent/brand/admin) declares its guard, the Authenticatable model
 * behind it, the abilities its tokens carry, and how the entity serialises — the
 * login/logout/refresh/me flow is identical and lives here.
 *
 * These endpoints are stateless: login verifies credentials against the entity
 * model directly (no session) and issues an ability-scoped personal access
 * token. Responses use Fama's single JSON envelope (response()->success()).
 */
abstract class AbstractAuthController extends Controller
{
    /**
     * The guard name backing this entity (also the token's primary ability):
     * one of talent / brand / admin.
     */
    abstract protected function guard(): string;

    /**
     * The Authenticatable Eloquent model class this guard resolves (App\Models
     * Talent / Brand / User).
     *
     * @return class-string<Model&Authenticatable>
     */
    abstract protected function model(): string;

    /**
     * Serialise the authenticated entity for the response body (its own API
     * resource, locale-resolved).
     */
    abstract protected function resource(Model $entity): JsonResource;

    /**
     * The abilities to stamp on a freshly issued token for this entity. Scopes
     * what the bearer may do; protected routes gate on these via the `abilities`
     * middleware.
     *
     * @return list<string>
     */
    protected function abilitiesFor(Model $entity): array
    {
        return [$this->guard()];
    }

    /**
     * Authenticate credentials and issue an ability-scoped token.
     *
     * Throttled by the route middleware; credentials are verified against the
     * entity model (constant-time hash check) with a single generic error to
     * avoid leaking which half was wrong.
     *
     * @throws ValidationException
     */
    public function login(LoginApiRequest $request): JsonResponse
    {
        /** @var (Model&Authenticatable&HasApiTokens)|null $entity */
        $entity = $this->model()::query()->where('email', $request->string('email'))->first();

        if ($entity === null || ! Hash::check((string) $request->string('password'), (string) $entity->getAuthPassword())) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return $this->respondWithToken($entity, $request->deviceName(), 200);
    }

    /**
     * Return the authenticated entity (the token's tokenable).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->success($this->resource($request->user()));
    }

    /**
     * Rotate the caller's token: revoke the one presented and issue a fresh
     * ability-scoped replacement. Sanctum tokens don't expire on their own, so
     * rotation is how a client renews a credential it wants to cycle.
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var Model&Authenticatable&HasApiTokens $entity */
        $entity = $request->user();
        $name = $entity->currentAccessToken()->name;

        $entity->currentAccessToken()->delete();

        return $this->respondWithToken($entity, $name, 200);
    }

    /**
     * Revoke the token presented with the request (single-device logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->success(null, __('Logged out.'));
    }

    /**
     * Issue a token for the entity and assemble the auth envelope: the plain
     * text token, its type + abilities, and the serialised entity. Bumps
     * last_login_at as a side effect of a fresh credential.
     *
     * @param  Model&Authenticatable&HasApiTokens  $entity
     */
    protected function respondWithToken(Model $entity, string $deviceName, int $status): JsonResponse
    {
        $abilities = $this->abilitiesFor($entity);
        $token = $entity->createToken($deviceName, $abilities);

        $entity->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
            $this->guard() => $this->resource($entity),
        ], null, [], $status);
    }
}
