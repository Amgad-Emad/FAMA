<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

/**
 * Per-file validation for the post media set (feed-spec §4): images
 * (jpg/png/webp) up to 8 MB, video (mp4/mov) up to 100 MB and 60 seconds.
 * Video duration is checked through ffprobe WHEN the binary is available;
 * without it the duration limit falls back to the client-side check (the
 * size cap still binds) — a documented operational requirement, not a silent
 * skip (docs/architecture.md, feed domain).
 */
class PostMediaFile implements ValidationRule
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const VIDEO_MIMES = ['video/mp4', 'video/quicktime'];

    /**
     * Validate one uploaded media file.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(__('The upload failed — try again.'));

            return;
        }

        $mime = (string) $value->getMimeType();

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            if ($value->getSize() > config('feed.limits.image_max_kb') * 1024) {
                $fail(__('Images may be at most :mb MB.', ['mb' => (int) (config('feed.limits.image_max_kb') / 1024)]));
            }

            return;
        }

        if (in_array($mime, self::VIDEO_MIMES, true)) {
            if ($value->getSize() > config('feed.limits.video_max_kb') * 1024) {
                $fail(__('Video may be at most :mb MB.', ['mb' => (int) (config('feed.limits.video_max_kb') / 1024)]));
            }

            $seconds = $this->probeDuration($value);
            $max = (int) config('feed.limits.video_max_seconds');

            if ($seconds !== null && $seconds > $max) {
                $fail(__('Video may be at most :seconds seconds.', ['seconds' => $max]));
            }

            return;
        }

        $fail(__('Only jpg, png, webp images and mp4, mov video are allowed.'));
    }

    /**
     * The video duration in seconds via ffprobe, or null when the binary is
     * unavailable or unreadable.
     */
    private function probeDuration(UploadedFile $file): ?float
    {
        $process = new Process([
            'ffprobe', '-v', 'error', '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1', $file->getRealPath(),
        ]);

        try {
            $process->run();
        } catch (\Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $duration = trim($process->getOutput());

        return is_numeric($duration) ? (float) $duration : null;
    }
}
