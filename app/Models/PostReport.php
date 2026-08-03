<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * PostReport — a report on a post OR comment (schema-master §7; the morph is
 * `reportable` despite the table name). One per reportable per reporter.
 * Status: open → reviewing → actioned | dismissed; `resolved_by` is the acting
 * admin. feed.auto_hide_reports open reports auto-hide the target (5Q).
 */
class PostReport extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reportable_type', 'reportable_id', 'reporter_type', 'reporter_id',
        'reason', 'note', 'status', 'resolved_by', 'resolved_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * The reported post or comment.
     *
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The reporting actor (talent or brand).
     *
     * @return MorphTo<Model, $this>
     */
    public function reporter(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The admin who resolved the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
