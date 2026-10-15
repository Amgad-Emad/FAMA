<?php

namespace Database\Seeders\Concerns;

/**
 * Generates on-brand gradient "cover" images locally with GD (no external
 * assets), so seeded talents show real, distinct image files through
 * medialibrary. Deterministic per seed string.
 */
trait GeneratesCoverImages
{
    /**
     * Create a JPEG cover for the given seed and return its temp path (consumed
     * by medialibrary's addMedia, which moves the file).
     */
    protected function cover(string $seed, int $w, int $h): string
    {
        $img = imagecreatetruecolor($w, $h);
        $n = crc32($seed);
        // Brand-arc hues only (orchid purples, yellows, mints) — the full
        // 0–360 wheel produced blue/navy covers, and blue is banned by the
        // FAMA branding. Deterministic per seed, like before.
        $hues = [45, 52, 58, 150, 160, 168, 275, 285, 295, 305, 315, 325];
        $hue = $hues[$n % count($hues)];

        $top = $this->hsl($hue, 46, 32);
        $bottom = $this->hsl(($hue + 340) % 360, 52, 13);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = imagecolorallocate(
                $img,
                (int) round($top[0] + ($bottom[0] - $top[0]) * $t),
                (int) round($top[1] + ($bottom[1] - $top[1]) * $t),
                (int) round($top[2] + ($bottom[2] - $top[2]) * $t),
            );
            imagefilledrectangle($img, 0, $y, $w, $y, $c);
        }

        imagealphablending($img, true);
        // Accent circles stay in the SAME hue family as the ground — a mint
        // circle alpha-blended over a purple gradient mixes to cyan/blue,
        // which the branding bans.
        $family = array_values(array_filter($hues, function ($h) use ($hue) {
            $d = abs($h - $hue);

            return min($d, 360 - $d) <= 40;
        })) ?: [$hue];
        for ($k = 0; $k < 4; $k++) {
            $col = $this->hsl($family[($n >> ($k + 2)) % count($family)], 60, 56);
            $c = imagecolorallocatealpha($img, $col[0], $col[1], $col[2], 96);
            $cx = ($n >> ($k * 3)) % $w;
            $cy = ($n >> ($k * 4 + 2)) % $h;
            $d = (int) (min($w, $h) * (0.32 + (($n >> $k) % 45) / 100));
            imagefilledellipse($img, $cx, $cy, $d, $d, $c);
        }

        // Unique per call: medialibrary MOVES the file, so a seed-only name raced
        // between parallel test processes ("file does not exist").
        $path = sys_get_temp_dir().'/fama_'.md5($seed).'_'.getmypid().'_'.bin2hex(random_bytes(4)).'.jpg';
        imagejpeg($img, $path, 84);
        imagedestroy($img);

        return $path;
    }

    /**
     * HSL (0-360, 0-100, 0-100) → RGB (0-255).
     *
     * @return array{int, int, int}
     */
    private function hsl(int $h, int $s, int $l): array
    {
        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0],
            $h < 120 => [$x, $c, 0],
            $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c],
            $h < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return [(int) round(($r + $m) * 255), (int) round(($g + $m) * 255), (int) round(($b + $m) * 255)];
    }
}
