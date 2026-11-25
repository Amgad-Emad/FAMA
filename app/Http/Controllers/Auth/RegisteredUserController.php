<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountCreationService;
use App\Support\Features;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Public self-registration. The applicant chooses an account type — talent or
 * brand ONLY (admin self-signup is forbidden, ADR-I). The chosen type decides
 * which entity is created (via AccountCreationService), which table the email
 * must be unique in, and which guard the new account is logged into.
 */
class RegisteredUserController extends Controller
{
    public function __construct(private readonly AccountCreationService $accounts) {}

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request for the chosen account type.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Brand self-registration is gated behind the `brands` feature flag: V1
        // ships talent-only, so only 'talent' is accepted when brands are off.
        $allowBrand = Features::brands();

        $type = $allowBrand ? $request->input('account_type') : 'talent';
        // Email uniqueness is scoped to the chosen entity's own table.
        $emailTable = $type === 'brand' ? 'brands' : 'talents';

        $data = $request->validate([
            'account_type' => ['required', Rule::in($allowBrand ? ['talent', 'brand'] : ['talent'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique($emailTable, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], $this->messages());

        if ($data['account_type'] === 'brand') {
            $account = $this->accounts->createBrand($data);
            $guard = 'brand';
        } else {
            $account = $this->accounts->createTalent($data);
            $guard = 'talent';
        }

        Auth::guard($guard)->login($account);
        $request->session()->regenerate();
        // Never honour a cross-guard intended URL (same rule as login).
        $request->session()->forget('url.intended');

        // A new talent starts the guided onboarding (ADR-AR) — five screens to a complete portfolio.
        return $guard === 'talent' ? redirect()->route('talent.onboarding') : redirect()->route('dashboard');
    }

    /**
     * Friendly field messages (the fama-ui register copy) — notably the taken
     * email, which points the person at logging in instead of a bare "has already
     * been taken".
     *
     * @return array<string, string>
     */
    private function messages(): array
    {
        $password = __('Use at least 8 characters, and mix in letters, numbers or symbols.');
        $email = __('Enter an email address like name@studio.com.');

        return [
            'name.required' => __('Add your full name.'),
            'email.required' => $email,
            'email.email' => $email,
            'email.unique' => __('This email already has an account. Log in instead.'),
            'password.required' => $password,
            'password.min' => $password,
            'password.confirmed' => __("The passwords don't match."),
        ];
    }
}
