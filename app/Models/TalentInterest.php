<?php

namespace App\Models;

use Database\Factories\TalentInterestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TalentInterest — one "willing to explore" entry (ADR-Z).
 *
 * A direction the talent wants to move toward, tied to a SKILL and (optionally)
 * a KIND OF WORK. Structured rather than free text on purpose: as prose it reads
 * as a mood and nobody acts on it, and brands could not filter it.
 *
 * The skill is deliberately NOT constrained to one the talent already holds —
 * wanting to move toward a discipline you do not yet list is the whole point of
 * the section. The speciality, when given, must belong to the chosen skill;
 * TalentInterestService is what enforces that.
 */
class TalentInterest extends Model
{
    /** @use HasFactory<TalentInterestFactory> */
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['talent_id', 'talent_type_id', 'speciality_id', 'note', 'position'];

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

    /**
     * The skill they want to move toward.
     *
     * @return BelongsTo<TalentType, $this>
     */
    public function talentType(): BelongsTo
    {
        return $this->belongsTo(TalentType::class);
    }

    /**
     * The kind of work, when they can name one. NULL = the whole skill.
     *
     * @return BelongsTo<Speciality, $this>
     */
    public function speciality(): BelongsTo
    {
        return $this->belongsTo(Speciality::class);
    }
}
