<?php

namespace App\Models;

use App\States\ProjectCredit\ProjectCreditState;
use Database\Factories\ProjectCreditFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

/**
 * ProjectCredit — "everyone who worked on it, and what each of them did"
 * (ADR-AA).
 *
 * `person + skill + role`. Skill says what someone CAN do; role says what they
 * did HERE — on a shoot with six people credited, only the role separates the
 * director from the assistant.
 *
 * An ON-PLATFORM credit (`talent_id` set) writes onto the tagged person's
 * portfolio, so it needs their confirmation and stays invisible until accepted.
 * An OFF-PLATFORM one (`name` only) is display-only on this project and is
 * `listed` from the start — there is nobody to ask and nothing to claim.
 */
class ProjectCredit extends Model
{
    /** @use HasFactory<ProjectCreditFactory> */
    use HasFactory, HasStates;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id', 'talent_id', 'name', 'talent_type_id', 'role',
        'status', 'responded_at', 'position', 'hidden_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'hidden_at' => 'datetime',
            'position' => 'integer',
            'status' => ProjectCreditState::class,
        ];
    }

    /**
     * The project this credit is on.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The collaborator, when they are on the platform.
     *
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    /**
     * The skill they were credited in — also the tab this project lands in on
     * their portfolio once accepted.
     *
     * @return BelongsTo<TalentType, $this>
     */
    public function talentType(): BelongsTo
    {
        return $this->belongsTo(TalentType::class);
    }

    /**
     * True when this credit belongs to someone on the platform, and therefore
     * needs their confirmation before it is shown anywhere.
     */
    public function isOnPlatform(): bool
    {
        return $this->talent_id !== null;
    }

    /**
     * The name to render: the tagged talent's, or the typed one for an
     * off-platform collaborator.
     */
    public function displayName(): ?string
    {
        return $this->relationLoaded('talent') && $this->talent !== null
            ? $this->talent->display_name
            : $this->name;
    }

    /**
     * Credits cleared for public display — accepted by the person, or an
     * off-platform credit that never needed confirming.
     *
     * @param  Builder<ProjectCredit>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->whereIn('status', ['accepted', 'listed']);
    }

    /**
     * Credits still waiting on the tagged person.
     *
     * @param  Builder<ProjectCredit>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }
}
