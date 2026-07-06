<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Brand login entity (the `brand` guard, `brands` provider).
 *
 * MINIMAL stub extended for the Phase 1E deal engine: the auth surface plus the
 * public identity (name/slug) and the `is_complete` deal-flow gate. The full
 * brand core — industry, stage, location, reach, aesthetics & satellites
 * (schema-master §4) — is built in Phase 1B and adds to this model; keep new
 * feature columns out until then.
 */
class Brand extends Authenticatable
{
    /** @use HasFactory<BrandFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'brands';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email', 'password', 'phone', 'name', 'slug',
        'is_complete', 'is_active', 'is_verified', 'is_published', 'meta',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_complete' => 'boolean',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'is_published' => 'boolean',
            'view_count' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Deals this brand is party to.
     *
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
