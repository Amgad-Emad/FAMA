<?php

namespace App\Support;

/**
 * URLs for the fama-ui static files (public/fama-ui) with a cache-busting
 * version = the file's modified time, so a CSS/JS edit reaches browsers on the
 * next load instead of waiting out a cached `?v=1`.
 */
class FamaAsset
{
    /** asset($path) plus `?v=<mtime>` (no version when the file is missing). */
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
