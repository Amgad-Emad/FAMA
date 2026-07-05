<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Brand login entity (the `brand` guard, `brands` provider).
 *
 * Phase 0 stub: this Authenticatable exists so the `brand` guard resolves and
 * the mobile API can issue tokens against it. The full `brands` table, its
 * satellites, and the rich model (per docs/specs/schema-master.md §4) are built
 * in Phase 1. Do NOT add feature columns/relations here yet.
 */
class Brand extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The database table backing the model (created in Phase 1).
     *
     * @var string
     */
    protected $table = 'brands';

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
