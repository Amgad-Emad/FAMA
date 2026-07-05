<?php

namespace App\Models;

use Database\Factories\LookTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * LookType — a "look" a model can do (editorial, runway, …) (schema-master §2).
 * `name` is translatable.
 */
class LookType extends Model
{
    /** @use HasFactory<LookTypeFactory> */
    use HasFactory, HasTranslations;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'name', 'position'];

    /**
     * Translatable attributes.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name'];

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
