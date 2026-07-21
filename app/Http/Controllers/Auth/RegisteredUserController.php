<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountCreationService;
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
        $type = $request->input('account_type');
        // Email uniqueness is scoped to the chosen entity's own table.
        $emailTable = $type === 'brand' ? 'brands' : 'talents';

        $data = $request->validate([
            'account_type' => ['required', Rule::in(['talent', 'brand'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique($emailTable, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

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

        return redirect()->route('dashboard');
    }
}
