<?php

namespace App\Auth;

use App\Models\Brand;
use App\Models\Talent;
use App\Models\User;
use App\Support\Features;

/**
 * Picks the password broker for a reset request.
 *
 * Fama has three login entities (talent, brand, admin user), each with its own
 * provider + broker in config/auth.php. The public forgot-password page is one
 * form, so the broker is resolved from the account the email belongs to — an
 * explicit account hint (the reset link's `account`, or `?role=` on the form)
 * wins when that account exists. The resolved account alias travels through the
 * reset link so the new-password step resets the SAME account.
 */
class PasswordBrokerResolver
{
    /** Account alias (MorphMap) → broker name. */
    private const BROKERS = ['talent' => 'talents', 'brand' => 'brands', 'user' => 'users'];

    /**
     * The account alias whose broker should handle this email: the hint when an
     * account of that type exists, else talent → brand (when the brands feature is
     * on) → admin user, else 'talent' (so the "can't find" status comes from the
     * talent broker — the public page's audience).
     */
    public function accountFor(string $email, ?string $hint = null): string
    {
        $order = array_values(array_unique(array_filter([$hint, 'talent', 'brand', 'user'])));

        foreach ($order as $account) {
            if ($this->allowed($account) && $this->exists($account, $email)) {
                return $account;
            }
        }

        return 'talent';
    }

    /**
     * Broker name for an account alias (unknown/disabled aliases fall back to talents).
     */
    public function broker(string $account): string
    {
        return $this->allowed($account) ? self::BROKERS[$account] : self::BROKERS['talent'];
    }

    /**
     * Whether an account alias is a valid, currently-enabled reset target.
     */
    public function allowed(?string $account): bool
    {
        if (! isset(self::BROKERS[$account ?? ''])) {
            return false;
        }

        return $account !== 'brand' || Features::brands();
    }

    /**
     * Whether an account of the given type exists for the email.
     */
    private function exists(string $account, string $email): bool
    {
        $model = match ($account) {
            'talent' => Talent::class,
            'brand' => Brand::class,
            'user' => User::class,
        };

        return $model::query()->where('email', $email)->exists();
    }
}
