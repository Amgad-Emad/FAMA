<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Support\Auth\Guards;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Role-aware PUBLIC login request. The public form serves talent and brand;
 * the submitted `role` selects the guard to authenticate against (absent role
 * defaults to talent). Staff sign in on their own separate screen —
 * /admin/login with AdminLoginRequest, which subclasses this to inherit the
 * rate-limit + single-active-identity pipeline while pinning the admin guard.
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
            'role' => ['nullable', 'string', Rule::in([UserRole::Talent->value, UserRole::Brand->value])],
        ];
    }

    /**
     * The role (and therefore guard) this login targets. Talent is the default
     * — admin is not reachable from the public form.
     */
    public function role(): UserRole
    {
        return UserRole::resolve($this->input('role'), UserRole::Talent);
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

        $guard = $this->role()->guard();

        if (! Auth::guard($guard)->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember'),
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Enforce a single active identity per session. Without this, a session
        // already authenticated on another guard would keep winning the
        // priority-ordered Guards::current() check, landing the user on the
        // wrong dashboard (e.g. a talent login while a brand session is live).
        foreach (Guards::names() as $other) {
            if ($other !== $guard) {
                Auth::guard($other)->logout();
            }
        }
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
