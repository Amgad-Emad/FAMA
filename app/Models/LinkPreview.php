<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LinkPreview — one row per unique URL, deduplicated by `url_hash`
 * (schema-master §7, ADR-F4). Provider + handle/resource are parsed from the
 * URL string with zero network at write time; FetchLinkPreviewJob enriches
 * best-effort afterwards (pending → ok | failed). `embed_html` only for
 * allowlisted providers, sanitized.
 */
class LinkPreview extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'url_hash', 'url', 'canonical_url', 'provider', 'provider_handle',
        'provider_resource_id', 'title', 'description', 'image_url',
        'embed_html', 'status', 'fetched_at', 'expires_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Every body placement of this URL.
     *
     * @return HasMany<LinkedUrl, $this>
     */
    public function linkedUrls(): HasMany
    {
        return $this->hasMany(LinkedUrl::class);
    }
}
