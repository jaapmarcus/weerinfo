<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Weather
{
    public static function descriptions(): array
    {
        return [
            0 => 'Helder',
            1 => 'Overwegend helder',
            2 => 'Gedeeltelijk bewolkt',
            3 => 'Bewolkt',
            45 => 'Mist',
            48 => 'Rijp­mist',
            51 => 'Lichte motregen',
            53 => 'Motregen',
            55 => 'Dichte motregen',
            56 => 'Lichte ijzel',
            57 => 'IJzel',
            61 => 'Lichte regen',
            63 => 'Regen',
            65 => 'Zware regen',
            66 => 'Lichte ijzelregen',
            67 => 'IJzelregen',
            71 => 'Lichte sneeuw',
            73 => 'Sneeuw',
            75 => 'Zware sneeuw',
            77 => 'Sneeuwkorrels',
            80 => 'Lichte buien',
            81 => 'Buien',
            82 => 'Zware buien',
            85 => 'Lichte sneeuwbuien',
            86 => 'Zware sneeuwbuien',
            95 => 'Onweer',
            96 => 'Onweer met lichte hagel',
            99 => 'Onweer met zware hagel',
        ];
    }

    public static function icons(): array
    {
        return [
            0 => 'sun',
            1 => 'sun',
            2 => 'cloud-sun',
            3 => 'cloud',
            45 => 'cloud-fog',
            48 => 'cloud-fog',
            51 => 'cloud-drizzle',
            53 => 'cloud-drizzle',
            55 => 'cloud-drizzle',
            56 => 'cloud-drizzle',
            57 => 'cloud-drizzle',
            61 => 'cloud-rain',
            63 => 'cloud-rain',
            65 => 'cloud-rain',
            66 => 'cloud-rain',
            67 => 'cloud-rain',
            71 => 'cloud-snow',
            73 => 'cloud-snow',
            75 => 'cloud-snow',
            77 => 'cloud-snow',
            80 => 'cloud-rain',
            81 => 'cloud-rain',
            82 => 'cloud-rain',
            85 => 'cloud-snow',
            86 => 'cloud-snow',
            95 => 'cloud-lightning',
            96 => 'cloud-lightning',
            99 => 'cloud-lightning',
        ];
    }

    public static function uvCategory(?float $uvIndex): array
    {
        return match (true) {
            $uvIndex === null => ['label' => __('Onbekend'), 'class' => 'text-neutral-500 dark:text-neutral-400'],
            $uvIndex < 3 => ['label' => __('Laag'), 'class' => 'text-emerald-600 dark:text-emerald-400'],
            $uvIndex < 6 => ['label' => __('Matig'), 'class' => 'text-yellow-600 dark:text-yellow-400'],
            $uvIndex < 8 => ['label' => __('Hoog'), 'class' => 'text-orange-600 dark:text-orange-400'],
            $uvIndex < 11 => ['label' => __('Zeer hoog'), 'class' => 'text-red-600 dark:text-red-400'],
            default => ['label' => __('Extreem'), 'class' => 'text-purple-600 dark:text-purple-400'],
        };
    }

    public static function forecast(float $latitude, float $longitude): ?array
    {
        $cacheKey = sprintf('weather:forecast:%s,%s:%s', $latitude, $longitude, now()->format('Y-m-d-H'));

        return Cache::remember($cacheKey, 900, function () use ($latitude, $longitude) {
            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current' => 'temperature_2m,apparent_temperature,weather_code',
                'hourly' => 'temperature_2m,precipitation_probability,weather_code,uv_index',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,uv_index_max',
                'timezone' => 'Europe/Amsterdam',
                'forecast_days' => 7,
            ]);

            if ($response->failed()) {
                return null;
            }

            $current = $response->json('current');
            $hourly = $response->json('hourly');
            $today = array_slice($hourly['time'], 0, 24);

            $daily = $response->json('daily');

            return [
                'current' => [
                    'temperature' => $current['temperature_2m'],
                    'apparent_temperature' => $current['apparent_temperature'],
                    'weather_code' => $current['weather_code'],
                ],

                'hours' => array_map(fn ($time, $index) => [
                    'time' => $time,
                    'temperature' => $hourly['temperature_2m'][$index],
                    'precipitation_probability' => $hourly['precipitation_probability'][$index],
                    'weather_code' => $hourly['weather_code'][$index],
                    'uv_index' => $hourly['uv_index'][$index],
                ], $today, array_keys($today)),

                'days' => array_map(fn ($date, $index) => [
                    'date' => $date,
                    'temperature_max' => $daily['temperature_2m_max'][$index],
                    'temperature_min' => $daily['temperature_2m_min'][$index],
                    'precipitation_probability_max' => $daily['precipitation_probability_max'][$index],
                    'weather_code' => $daily['weather_code'][$index],
                    'uv_index_max' => $daily['uv_index_max'][$index],
                ], $daily['time'], array_keys($daily['time'])),
            ];
        });
    }
}
