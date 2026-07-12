<?php

namespace App\Models;

use Database\Factories\BrandSignalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Brand signal (schema-master §4) — an append-only behaviour event (view / save
 * / brief_sent / profile_open) feeding the preference engine. Write-once: no
 * `updated_at`, never edited. Analytics-store candidate at volume (docs/schema.md).
 */
class BrandSignal extends Model
{
    /** @use HasFactory<BrandSignalFactory> */
    use HasFactory;

    /** Append-only — no updated_at. */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['brand_id', 'talent_id', 'action_type', 'context'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<Talent, $this> */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}
