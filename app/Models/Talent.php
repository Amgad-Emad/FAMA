<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Talent login entity (the `talent` guard, `talents` provider).
 *
 * Phase 0 stub: this Authenticatable exists so the `talent` guard resolves and
 * the mobile API can issue tokens against it. The full `talents` table, the
 * block system, and the rich model (per docs/specs/schema-master.md §1) are
 * built in Phase 1A. Do NOT add feature columns/relations here yet.
 */
class Talent extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The database table backing the model (created in Phase 1A).
     *
     * @var string
     */
    protected $table = 'talents';

    /**
     * Mass-assignable attributes for the Phase 0 auth surface.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
    ];

    /**
     * Attributes hidden from serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
