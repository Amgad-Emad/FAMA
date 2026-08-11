<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A daily profile-view bucket: one row per (talent, day), `views` incremented
 * per view. Never appears in a morph column, so it needs no MorphMap alias.
 */
class TalentProfileView extends Model
{
    protected $fillable = ['talent_id', 'viewed_on', 'views'];

    protected function casts(): array
    {
        return [
            'viewed_on' => 'date',
            'views' => 'integer',
        ];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}
