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
        $hue = $n % 360;

        $top = $this->hsl($hue, 46, 32);
        $bottom = $this->hsl(($hue + 28) % 360, 52, 13);
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
        for ($k = 0; $k < 4; $k++) {
            $col = $this->hsl(($hue + 55 + $k * 32) % 360, 60, 56);
            $c = imagecolorallocatealpha($img, $col[0], $col[1], $col[2], 96);
            $cx = ($n >> ($k * 3)) % $w;
            $cy = ($n >> ($k * 4 + 2)) % $h;
            $d = (int) (min($w, $h) * (0.32 + (($n >> $k) % 45) / 100));
            imagefilledellipse($img, $cx, $cy, $d, $d, $c);
        }

        $path = sys_get_temp_dir().'/fama_'.md5($seed).'.jpg';
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
