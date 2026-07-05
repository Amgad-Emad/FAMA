<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Review — a client/peer testimonial (schema-master §2). Moderated via
 * `is_approved`. The reviewer avatar is an uploaded asset; `body` is intentionally
 * NOT translatable (a testimonial is kept in the language it was written).
 */
class Review extends Model implements HasMedia
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'talent_id', 'reviewer_name', 'reviewer_role', 'reviewer_company',
        'rating', 'body', 'project_type', 'is_approved', 'reviewed_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Single uploaded reviewer avatar.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * Thumbnail conversion for the avatar.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(200)->height(200)->nonQueued();
    }

    /**
     * Reviewer avatar URL from the media library (null if none).
     */
    public function getReviewerAvatarUrlAttribute(): ?string
    {
        $this->loadMissing('media');

        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    /**
     * Only approved reviews are public.
     *
     * @param  Builder<Review>  $query
     */
    public function scopeApproved($query): void
    {
        $query->where('is_approved', true);
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
