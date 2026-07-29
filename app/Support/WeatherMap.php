<?php

namespace App\Support;

use Carbon\CarbonInterface;
use GdImage;

class WeatherMap
{
    private const WIDTH = 780;

    private const HEIGHT = 820;

    private const PADDING = 50;

    private const CARD_WIDTH = 120;

    private const CARD_HEIGHT = 64;

    private const CARD_GAP = 10;

    private const MAP_TOP = 84;

    private const MAP_BOTTOM = self::HEIGHT - 56;

    /**
     * Outline data: Netherlands national boundary (coastline + land borders
     * with Belgium/Germany, since a country polygon's ring is one continuous
     * outline covering both), simplified from Natural Earth's 1:50m admin-0
     * countries dataset (public domain). One entry per polygon ring — the
     * mainland plus the larger Wadden/Zeeland islands.
     */
    private const OUTLINE_PATH = 'data/netherlands-outline.json';

    /**
     * Water data: IJsselmeer/Markermeer, from Natural Earth's 1:10m lakes
     * dataset (public domain). Stored as two rings — the outer lake shape,
     * and an inner ring for the Flevoland polder reclaimed from its middle,
     * which Natural Earth models as a hole in the lake polygon.
     */
    private const WATER_PATH = 'data/netherlands-water.json';

    /**
     * Render an abstract overview map of the given locations, plotted over
     * the Dutch national outline, as a PNG. Each location must include
     * region, lat, lon, temperature_max, temperature_min and weather_code.
     *
     * @param  array<int, array{region: string, lat: float, lon: float, temperature_max: float, temperature_min: float, weather_code: int}>  $locations
     */
    public static function render(array $locations, CarbonInterface $date): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $mist = imagecolorallocate($image, 250, 251, 252);
        $ink = imagecolorallocate($image, 18, 35, 61);
        $slate = imagecolorallocate($image, 91, 107, 124);
        $hairline = imagecolorallocate($image, 220, 226, 232);
        $white = imagecolorallocate($image, 255, 255, 255);
        $accentBlue = imagecolorallocate($image, 42, 91, 168);
        $landFill = imagecolorallocate($image, 241, 244, 247);

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $mist);

        self::drawHeader($image, $date, $ink, $slate, $hairline);
        self::drawFooter($image, $slate, $hairline);

        $outline = self::loadRings(self::OUTLINE_PATH);
        $projection = self::buildProjection(array_merge(...$outline));

        self::drawOutline($image, $outline, $projection, $landFill, $slate);
        self::drawWater($image, self::loadRings(self::WATER_PATH), $projection, $mist, $landFill, $slate);

        $cards = self::resolveOverlaps(array_map(function (array $location) use ($projection) {
            [$location['x'], $location['y']] = self::projectPoint($projection, $location['lon'], $location['lat']);

            return $location;
        }, $locations));

        foreach ($cards as $card) {
            self::drawCard($image, $card, $ink, $slate, $white, $hairline, $accentBlue);
        }

        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    /**
     * @return array<int, array<int, array{0: float, 1: float}>>
     */
    private static function loadRings(string $relativePath): array
    {
        $path = resource_path($relativePath);

        return json_decode(file_get_contents($path), true);
    }

    private static function drawOutline(GdImage $image, array $rings, array $projection, int $fill, int $stroke): void
    {
        imagesetthickness($image, 2);

        foreach ($rings as $ring) {
            self::drawRing($image, $ring, $projection, $fill, $stroke);
        }

        imagesetthickness($image, 1);
    }

    /**
     * Draw the IJsselmeer/Markermeer water body, then punch the Flevoland
     * polder (the lake polygon's inner ring) back out as land, matching how
     * Natural Earth models it as a hole reclaimed from the lake's middle.
     */
    private static function drawWater(GdImage $image, array $rings, array $projection, int $water, int $land, int $stroke): void
    {
        imagesetthickness($image, 2);

        if (isset($rings[0])) {
            self::drawRing($image, $rings[0], $projection, $water, $stroke);
        }

        if (isset($rings[1])) {
            self::drawRing($image, $rings[1], $projection, $land, $stroke);
        }

        imagesetthickness($image, 1);
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring
     * @param  array{offsetX: float, offsetY: float, scale: float, lonScale: float, lonMin: float, latMax: float}  $projection
     */
    private static function drawRing(GdImage $image, array $ring, array $projection, int $fill, int $stroke): void
    {
        $points = [];

        foreach ($ring as [$lon, $lat]) {
            [$x, $y] = self::projectPoint($projection, $lon, $lat);
            $points[] = $x;
            $points[] = $y;
        }

        imagefilledpolygon($image, $points, $fill);
        imagepolygon($image, $points, $stroke);
    }

    private static function drawHeader(GdImage $image, CarbonInterface $date, int $ink, int $slate, int $hairline): void
    {
        imagestring($image, 5, self::PADDING, 22, 'WEERBERICHT VANDAAG', $ink);
        imagestring($image, 3, self::PADDING, 44, mb_strtoupper($date->locale(config('app.locale'))->translatedFormat('l d F Y')), $slate);
        imageline($image, self::PADDING, 68, self::WIDTH - self::PADDING, 68, $hairline);
    }

    private static function drawFooter(GdImage $image, int $slate, int $hairline): void
    {
        imageline($image, self::PADDING, self::HEIGHT - 40, self::WIDTH - self::PADDING, self::HEIGHT - 40, $hairline);
        imagestring($image, 2, self::PADDING, self::HEIGHT - 28, 'WEERINFO.NL', $slate);
    }

    /**
     * Build a lon/lat -> canvas projection from a set of points, compensating
     * for longitude compression at Dutch latitudes and fitting the result
     * into the map box (inset by card half-width so cards near the edge stay
     * on-canvas) while preserving the true width:height ratio.
     *
     * @param  array<int, array{0: float, 1: float}>  $points
     * @return array{offsetX: float, offsetY: float, scale: float, lonScale: float, lonMin: float, latMax: float}
     */
    private static function buildProjection(array $points): array
    {
        $mapLeft = self::PADDING + self::CARD_WIDTH / 2;
        $mapRight = self::WIDTH - self::PADDING - self::CARD_WIDTH / 2;

        $lons = array_column($points, 0);
        $lats = array_column($points, 1);

        $latMin = min($lats);
        $latMax = max($lats);
        $lonMin = min($lons);
        $lonMax = max($lons);

        $lonScale = cos(deg2rad(($latMin + $latMax) / 2));

        $latSpan = max($latMax - $latMin, 0.01);
        $lonSpan = max(($lonMax - $lonMin) * $lonScale, 0.01);

        $boxWidth = $mapRight - $mapLeft;
        $boxHeight = self::MAP_BOTTOM - self::MAP_TOP;
        $scale = min($boxWidth / $lonSpan, $boxHeight / $latSpan);

        $usedWidth = $lonSpan * $scale;
        $usedHeight = $latSpan * $scale;

        return [
            'offsetX' => $mapLeft + ($boxWidth - $usedWidth) / 2,
            'offsetY' => self::MAP_TOP + ($boxHeight - $usedHeight) / 2,
            'scale' => $scale,
            'lonScale' => $lonScale,
            'lonMin' => $lonMin,
            'latMax' => $latMax,
        ];
    }

    /**
     * @param  array{offsetX: float, offsetY: float, scale: float, lonScale: float, lonMin: float, latMax: float}  $projection
     * @return array{0: float, 1: float}
     */
    private static function projectPoint(array $projection, float $lon, float $lat): array
    {
        $x = $projection['offsetX'] + ($lon - $projection['lonMin']) * $projection['lonScale'] * $projection['scale'];
        $y = $projection['offsetY'] + ($projection['latMax'] - $lat) * $projection['scale'];

        return [$x, $y];
    }

    /**
     * Nearby cities can project close enough that their cards would overlap.
     * Nudge overlapping cards apart (and keep them within the map bounds)
     * with a small iterative separation pass, since only a handful of fixed
     * points are ever involved this converges almost immediately.
     *
     * @param  array<int, array<string, mixed>>  $points
     * @return array<int, array<string, mixed>>
     */
    private static function resolveOverlaps(array $points): array
    {
        $minDx = self::CARD_WIDTH + self::CARD_GAP;
        $minDy = self::CARD_HEIGHT + self::CARD_GAP;

        $minX = self::PADDING + self::CARD_WIDTH / 2;
        $maxX = self::WIDTH - self::PADDING - self::CARD_WIDTH / 2;
        $minY = self::MAP_TOP + self::CARD_HEIGHT / 2;
        $maxY = self::MAP_BOTTOM - self::CARD_HEIGHT / 2;

        for ($iteration = 0; $iteration < 200; $iteration++) {
            $moved = false;

            for ($i = 0; $i < count($points); $i++) {
                for ($j = $i + 1; $j < count($points); $j++) {
                    $dx = $points[$j]['x'] - $points[$i]['x'];
                    $dy = $points[$j]['y'] - $points[$i]['y'];

                    if ($dx === 0.0 && $dy === 0.0) {
                        $dx = 0.01;
                    }

                    $overlapX = $minDx - abs($dx);
                    $overlapY = $minDy - abs($dy);

                    if ($overlapX <= 0 || $overlapY <= 0) {
                        continue;
                    }

                    $moved = true;

                    if ($overlapX < $overlapY) {
                        $push = ($overlapX / 2) * ($dx >= 0 ? 1 : -1);
                        $points[$i]['x'] -= $push;
                        $points[$j]['x'] += $push;
                    } else {
                        $push = ($overlapY / 2) * ($dy >= 0 ? 1 : -1);
                        $points[$i]['y'] -= $push;
                        $points[$j]['y'] += $push;
                    }
                }
            }

            if (! $moved) {
                break;
            }
        }

        return array_map(function (array $point) use ($minX, $maxX, $minY, $maxY) {
            $point['x'] = (int) round(min(max($point['x'], $minX), $maxX));
            $point['y'] = (int) round(min(max($point['y'], $minY), $maxY));

            return $point;
        }, $points);
    }

    private static function drawCard(GdImage $image, array $location, int $ink, int $slate, int $white, int $hairline, int $accentBlue): void
    {
        $x = $location['x'] - self::CARD_WIDTH / 2;
        $y = $location['y'] - self::CARD_HEIGHT / 2;

        self::filledRoundedRectangle($image, (int) $x, (int) $y, self::CARD_WIDTH, self::CARD_HEIGHT, 10, $white);
        self::roundedRectangleOutline($image, (int) $x, (int) $y, self::CARD_WIDTH, self::CARD_HEIGHT, 10, $hairline);

        self::drawIcon($image, (int) $x + 22, (int) $y + (int) (self::CARD_HEIGHT / 2), self::bucket((int) $location['weather_code']), $ink, $slate, $accentBlue);

        imagestring($image, 5, (int) $x + 44, (int) $y + 12, sprintf('%d', (int) round($location['temperature_max'])).chr(176), $accentBlue);
        imagestring($image, 2, (int) $x + 44, (int) $y + 38, sprintf('min %d', (int) round($location['temperature_min'])).chr(176), $slate);
    }

    public static function bucket(int $code): string
    {
        return match (true) {
            in_array($code, [0, 1], true) => 'sun',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'snow',
            in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99], true) => 'rain',
            default => 'cloud',
        };
    }

    private static function drawIcon(GdImage $image, int $cx, int $cy, string $bucket, int $ink, int $slate, int $accentBlue): void
    {
        $gold = imagecolorallocate($image, 214, 158, 46);

        match ($bucket) {
            'sun' => (function () use ($image, $cx, $cy, $gold) {
                for ($i = 0; $i < 8; $i++) {
                    $angle = $i * (M_PI * 2 / 8);
                    $x1 = $cx + cos($angle) * 13;
                    $y1 = $cy + sin($angle) * 13;
                    $x2 = $cx + cos($angle) * 19;
                    $y2 = $cy + sin($angle) * 19;
                    imageline($image, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $gold);
                }
                imagefilledellipse($image, $cx, $cy, 20, 20, $gold);
            })(),
            'rain' => (function () use ($image, $cx, $cy, $slate, $accentBlue) {
                imagefilledellipse($image, $cx - 5, $cy - 2, 20, 16, $slate);
                imagefilledellipse($image, $cx + 6, $cy - 4, 16, 14, $slate);
                for ($i = 0; $i < 3; $i++) {
                    $lx = $cx - 8 + $i * 8;
                    imageline($image, $lx, $cy + 8, $lx - 3, $cy + 16, $accentBlue);
                }
            })(),
            'snow' => (function () use ($image, $cx, $cy, $slate) {
                imagefilledellipse($image, $cx - 5, $cy - 2, 20, 16, $slate);
                imagefilledellipse($image, $cx + 6, $cy - 4, 16, 14, $slate);
                for ($i = 0; $i < 3; $i++) {
                    $dx = $cx - 8 + $i * 8;
                    imagefilledellipse($image, $dx, $cy + 12, 4, 4, $slate);
                }
            })(),
            default => (function () use ($image, $cx, $cy, $slate) {
                imagefilledellipse($image, $cx - 5, $cy, 22, 18, $slate);
                imagefilledellipse($image, $cx + 7, $cy - 3, 18, 16, $slate);
            })(),
        };
    }

    private static function filledRoundedRectangle(GdImage $image, int $x, int $y, int $width, int $height, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
        imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
    }

    private static function roundedRectangleOutline(GdImage $image, int $x, int $y, int $width, int $height, int $radius, int $color): void
    {
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $color);
    }
}
