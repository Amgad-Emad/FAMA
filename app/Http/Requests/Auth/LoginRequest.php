<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Role-aware login request. A single login form serves all three entities; the
 * submitted `role` selects the guard to authenticate against. When `role` is
 * absent it defaults to admin (the only migrated auth table in Phase 0), which
 * also keeps the Breeze User-based tests green. See docs/decisions.md (OPEN:
 * single role-aware login vs. per-role login screens).
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'string', Rule::in(UserRole::values())],
        ];
    }

    /**
     * The role (and therefore guard) this login targets.
     */
    public function role(): UserRole
    {
        return UserRole::resolve($this->input('role'));
    }

    /**
     * Attempt to authenticate the request's credentials against the resolved
     * guard.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::guard($this->role()->guard())->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember'),
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request (scoped per role so
     * the three entities don't share a throttle bucket).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->role()->value.'|'.$this->ip()
        );
    }
}
