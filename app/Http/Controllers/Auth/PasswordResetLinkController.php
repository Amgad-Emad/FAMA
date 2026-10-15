<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PasswordBrokerResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request: resolve which account
     * (talent / brand / admin user) the email belongs to and send that broker's link.
     *
     * @throws ValidationException
     */
    public function store(Request $request, PasswordBrokerResolver $brokers): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'role' => ['nullable', 'string'],
        ]);

        // One public form, three login entities: send the link through the broker
        // of the account this email belongs to (talent first; `role` is a hint).
        $account = $brokers->accountFor($request->string('email')->toString(), $request->input('role'));

        $status = Password::broker($brokers->broker($account))->sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                        // Flash the address so the "sent" state can name it.
                        ->withInput($request->only('email'))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
