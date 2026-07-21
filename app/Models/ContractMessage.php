<?php

namespace App\Models;

use App\States\ContractMessage\ContractMessageState;
use Database\Factories\ContractMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\ModelStates\HasStates;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Contract message (schema-master §3) — one line in the contract-room timeline. `type`
 * distinguishes a human `message` from an immutable `system_event` /
 * `action_summary`. `status` (sent → read) is a state machine; `read_at` is its
 * projection. `sender` is a polymorphic actor (null for system). `is_rich` marks a
 * body that holds sanitized HTML (an application brief); files upload to the
 * `attachments` media collection.
 */
class ContractMessage extends Model implements HasMedia
{
    /** @use HasFactory<ContractMessageFactory> */
    use HasFactory, HasStates, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_id', 'contract_step_id', 'sender_type', 'sender_id', 'sender_role',
        'type', 'body', 'is_rich', 'attachments', 'meta', 'status', 'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractMessageState::class,
            'attachments' => 'array',
            'meta' => 'array',
            'is_rich' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Uploaded application/message files (originals, no conversions).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    /**
     * The uploaded attachments as [{name, url, size}], for the contract-room timeline.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFileAttachmentsAttribute(): array
    {
        return $this->getMedia('attachments')->map(fn ($media) => [
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'size' => $media->size,
        ])->all();
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<ContractStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ContractStep::class, 'contract_step_id');
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
     * @param  Builder<ContractMessage>  $query
     */
    public function scopeHumanUnreadFor(Builder $query, string $role): void
    {
        $query->where('type', 'message')->where('status', 'sent')->where('sender_role', '!=', $role);
    }
}
