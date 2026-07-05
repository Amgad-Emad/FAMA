<?php

namespace App\Enums;

/**
 * The three Fama login entities. The enum value is intentionally identical to
 * the guard name so role <-> guard resolution is a single source of truth used
 * by the role-aware auth controllers, middleware, and views.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Brand = 'brand';
    case Talent = 'talent';

    /**
     * The session/auth guard backing this role (config/auth.php).
     */
    public function guard(): string
    {
        return $this->value;
    }

    /**
     * The Eloquent auth provider backing this role (config/auth.php).
     */
    public function provider(): string
    {
        return match ($this) {
            self::Admin => 'users',
            self::Brand => 'brands',
            self::Talent => 'talents',
        };
    }

    /**
     * All role string values, e.g. for validation `in:` rules.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }

    /**
     * Resolve a role from arbitrary (untrusted) input, falling back to a
     * default. Used by the single role-aware login to pick the target guard.
     */
    public static function resolve(?string $role, self $default = self::Admin): self
    {
        return $role !== null ? (self::tryFrom($role) ?? $default) : $default;
    }
}
