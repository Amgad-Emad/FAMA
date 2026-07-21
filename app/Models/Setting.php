<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide key→JSON setting (schema-master §6). Read/written exclusively
 * through App\Services\SettingsService (which caches the full map); `value`
 * holds any JSON scalar or structure.
 */
class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
