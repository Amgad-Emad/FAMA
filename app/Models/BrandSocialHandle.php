<?php

namespace App\Models;

use Database\Factories\BrandSocialHandleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Brand social handle (schema-master §4) — a settings-stage, reorderable list
 * item. External `url` stays a plain column.
 */
class BrandSocialHandle extends Model
{
    /** @use HasFactory<BrandSocialHandleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['brand_id', 'platform', 'handle', 'url', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
