<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Equipment — a crew kit item (schema-master §2). brand/model/name stay plain
 * (queryable — "who owns a RED camera"); only `notes` is translatable.
 */
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory, HasTranslations;

    /**
     * Explicit table (the noun is uncountable).
     *
     * @var string
     */
    protected $table = 'equipment';

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'category', 'brand', 'model', 'name', 'notes', 'position'];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['notes'];

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
