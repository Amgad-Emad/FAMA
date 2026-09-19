<?php

namespace App\Models;

use Database\Factories\TalentSocialHandleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TalentSocialHandle — "anywhere else the work lives" (ADR-Z, the About
 * section). A reorderable list mirroring `brand_social_handles`, so both sides
 * of the platform describe a social presence identically.
 */
class TalentSocialHandle extends Model
{
    /** @use HasFactory<TalentSocialHandleFactory> */
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'platform', 'handle', 'url', 'position'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /**
     * Owning talent.
     *
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}
