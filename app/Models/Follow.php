<?php

namespace App\Models;

use App\Observers\FollowObserver;
use Database\Factories\FollowFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Follow — polymorphic edge (schema-master §7): `follower` (talent/brand) →
 * `followable` (talent; brand behind feed.allow_following_brands). Append-only
 * (created_at, no updated_at); unfollow deletes the row. Counter maintenance
 * lives in FollowObserver (ADR-F6); self-follow is forbidden by policy.
 */
#[ObservedBy(FollowObserver::class)]
class Follow extends Model
{
    /** @use HasFactory<FollowFactory> */
    use HasFactory;

    public const ?string UPDATED_AT = null;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'follower_type', 'follower_id', 'followable_type', 'followable_id', 'created_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * The following actor.
     *
     * @return MorphTo<Model, $this>
     */
    public function follower(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The followed party.
     *
     * @return MorphTo<Model, $this>
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }
}
