<?php

namespace App\Models;

use App\States\DealMessage\DealMessageState;
use Database\Factories\DealMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\ModelStates\HasStates;

/**
 * Deal message (schema-master §3) — one line in the deal-room timeline. `type`
 * distinguishes a human `message` from an immutable `system_event` /
 * `action_summary`. `status` (sent → read) is a state machine; `read_at` is its
 * projection. `sender` is a polymorphic actor (null for system).
 */
class DealMessage extends Model
{
    /** @use HasFactory<DealMessageFactory> */
    use HasFactory, HasStates;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'deal_id', 'deal_step_id', 'sender_type', 'sender_id', 'sender_role',
        'type', 'body', 'attachments', 'status', 'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DealMessageState::class,
            'attachments' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * @return BelongsTo<DealStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(DealStep::class, 'deal_step_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * True for immutable audit lines (system events / action summaries) that are
     * never marked read.
     */
    public function isSystem(): bool
    {
        return in_array($this->type, ['system_event', 'action_summary'], true);
    }

    /**
     * @param  Builder<DealMessage>  $query
     */
    public function scopeHumanUnreadFor(Builder $query, string $role): void
    {
        $query->where('type', 'message')->where('status', 'sent')->where('sender_role', '!=', $role);
    }
}
