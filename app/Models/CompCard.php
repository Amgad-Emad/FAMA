<?php

namespace App\Models;

use Database\Factories\CompCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompCard — model stats, 1:1 with a talent (schema-master §2). Kept in its own
 * table so these model-only fields don't sit NULL on every crew/creative profile.
 */
class CompCard extends Model
{
    /** @use HasFactory<CompCardFactory> */
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'height_cm', 'bust_cm', 'waist_cm', 'hips_cm',
        'shoe_size', 'dress_size', 'hair_color', 'eye_color', 'skin_tone', 'measurements',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height_cm' => 'integer',
            'bust_cm' => 'integer',
            'waist_cm' => 'integer',
            'hips_cm' => 'integer',
            'measurements' => 'array',
        ];
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
