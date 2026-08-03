<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * The 5S contrast gate: recomputes every derived Daylight token exactly as
 * the CSS color-mix(in oklch) formulas do (sRGB → OKLab → LCH interpolation
 * with the shorter hue arc) and fails when any WCAG ratio drops below the
 * hard requirements:
 *   ink       on ground|surface ≥ 7:1
 *   ink-soft  on ground|surface ≥ 4.5:1
 *   ink-faint on ground|surface ≥ 4.5:1  (carries 12px mono meta = small text)
 *   daylight  on ground|surface ≥ 4.5:1
 *
 * The formula percentages here MUST mirror resources/css/daylight.css — the
 * command exists so a palette edit that breaks contrast breaks the build.
 * It also prints the computed hexes (the @supports fallback values).
 */
class CheckFeedContrast extends Command
{
    protected $signature = 'feed:check-contrast';

    protected $description = 'Verify WCAG contrast for the derived Daylight tokens (both themes)';

    /**
     * The CSS formulas, one entry per theme:
     * token => [mix-target hex, percentage] applied to the base.
     *
     * @var array<string, array{base: string, daylight: string, mixes: array<string, array{string, float}>}>
     */
    private array $themes = [
        'light' => [
            'base' => '#EDEFF3',
            'daylight' => '#3D4FD6',
            'mixes' => [
                'ground-sunk' => ['#14110B', 4],
                'surface' => ['#FFFFFF', 55],
                'surface-sunk' => ['#14110B', 2],
                'seam' => ['#14110B', 9],
                'ink' => ['#14110B', 92],
                'ink-soft' => ['#14110B', 64],
                'ink-faint' => ['#14110B', 56], // tuned up from 48 to clear 4.5:1
            ],
        ],
        'dark' => [
            'base' => '#0D0F13',
            'daylight' => '#7B8AFF',
            'mixes' => [
                'ground-sunk' => ['#000000', 35],
                'surface' => ['#FFFFFF', 6],
                'surface-sunk' => ['#000000', 18],
                'seam' => ['#FFFFFF', 12],
                'ink' => ['#FFF6E8', 94],
                'ink-soft' => ['#FFF6E8', 62],
                'ink-faint' => ['#FFF6E8', 56], // tuned up from 44 to clear 4.5:1
            ],
        ],
    ];

    /**
     * Compute, print and gate.
     */
    public function handle(): int
    {
        $failures = 0;

        foreach ($this->themes as $themeName => $theme) {
            $tokens = ['ground' => $theme['base']];

            foreach ($theme['mixes'] as $token => [$target, $percent]) {
                $tokens[$token] = $this->mixOklch($theme['base'], $target, $percent);
            }

            $this->line("<info>{$themeName}</info>");

            foreach ($tokens as $token => $hex) {
                $this->line(sprintf('  --dl-%-13s %s', $token.':', $hex));
            }

            $gates = [
                ['ink', 'ground', 7.0], ['ink', 'surface', 7.0],
                ['ink-soft', 'ground', 4.5], ['ink-soft', 'surface', 4.5],
                ['ink-faint', 'ground', 4.5], ['ink-faint', 'surface', 4.5],
            ];

            foreach ($gates as [$fg, $bg, $minimum]) {
                $failures += $this->gate($themeName, "--dl-{$fg}", $tokens[$fg], "--dl-{$bg}", $tokens[$bg], $minimum);
            }

            foreach (['ground', 'surface'] as $bg) {
                $failures += $this->gate($themeName, '--dl-daylight', $theme['daylight'], "--dl-{$bg}", $tokens[$bg], 4.5);
            }
        }

        if ($failures > 0) {
            $this->error("{$failures} contrast gate(s) failed — adjust the mix percentages in daylight.css AND this command together.");

            return self::FAILURE;
        }

        $this->info('All Daylight contrast gates pass.');

        return self::SUCCESS;
    }

    /**
     * One WCAG gate; returns 1 on failure.
     */
    private function gate(string $theme, string $fgName, string $fg, string $bgName, string $bg, float $minimum): int
    {
        $ratio = $this->contrastRatio($fg, $bg);
        $pass = $ratio >= $minimum;

        $this->line(sprintf(
            '  %s %s on %s = %.2f (need %.1f)',
            $pass ? '✓' : '✗', $fgName, $bgName, $ratio, $minimum,
        ));

        return $pass ? 0 : 1;
    }

    /**
     * CSS color-mix(in oklch, base, target p%): LCH interpolation, shorter
     * hue arc.
     */
    private function mixOklch(string $baseHex, string $targetHex, float $percent): string
    {
        $a = $this->toLch($this->rgbToOklab($this->hexToRgb($baseHex)));
        $b = $this->toLch($this->rgbToOklab($this->hexToRgb($targetHex)));
        $t = $percent / 100;

        $dh = $b[2] - $a[2];
        if ($dh > 180) {
            $dh -= 360;
        }
        if ($dh < -180) {
            $dh += 360;
        }

        $mixed = [
            $a[0] + ($b[0] - $a[0]) * $t,
            $a[1] + ($b[1] - $a[1]) * $t,
            $a[2] + $dh * $t,
        ];

        return $this->rgbToHex($this->oklabToRgb($this->fromLch($mixed)));
    }

    /**
     * WCAG 2.x contrast ratio between two hex colours.
     */
    private function contrastRatio(string $hexA, string $hexB): float
    {
        $la = $this->luminance($hexA);
        $lb = $this->luminance($hexB);
        [$hi, $lo] = $la >= $lb ? [$la, $lb] : [$lb, $la];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map($this->srgbToLinear(...), $this->hexToRgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** @return array{float, float, float} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    /** @param array{float, float, float} $rgb */
    private function rgbToHex(array $rgb): string
    {
        return sprintf('#%02X%02X%02X', ...array_map(
            fn (float $c): int => (int) round(max(0, min(1, $c)) * 255),
            $rgb,
        ));
    }

    private function srgbToLinear(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    private function linearToSrgb(float $c): float
    {
        return $c <= 0.0031308 ? 12.92 * $c : 1.055 * ($c ** (1 / 2.4)) - 0.055;
    }

    /** @param array{float, float, float} $rgb @return array{float, float, float} */
    private function rgbToOklab(array $rgb): array
    {
        [$r, $g, $b] = array_map($this->srgbToLinear(...), $rgb);

        $l = (0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b) ** (1 / 3);
        $m = (0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b) ** (1 / 3);
        $s = (0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b) ** (1 / 3);

        return [
            0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s,
            1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s,
            0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s,
        ];
    }

    /** @param array{float, float, float} $lab @return array{float, float, float} */
    private function oklabToRgb(array $lab): array
    {
        [$L, $a, $b] = $lab;

        $l = ($L + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m = ($L - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($L - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        return array_map($this->linearToSrgb(...), [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ]);
    }

    /** @param array{float, float, float} $lab @return array{float, float, float} */
    private function toLch(array $lab): array
    {
        [$L, $a, $b] = $lab;
        $h = rad2deg(atan2($b, $a));

        return [$L, sqrt($a * $a + $b * $b), $h < 0 ? $h + 360 : $h];
    }

    /** @param array{float, float, float} $lch @return array{float, float, float} */
    private function fromLch(array $lch): array
    {
        [$L, $c, $h] = $lch;

        return [$L, $c * cos(deg2rad($h)), $c * sin(deg2rad($h))];
    }
}
