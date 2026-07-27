<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Support\Weather;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $query = trim((string) $request->query('q', ''));
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $name = $request->query('name');

        $location = null;
        $results = [];

        if (is_numeric($lat) && is_numeric($lng) && abs($lat) <= 90 && abs($lng) <= 180) {
            if ($redirect = $this->redirectToKnownLocation((float) $lat, (float) $lng)) {
                return $redirect;
            }

            $location = [
                'name' => filled($name) ? $name : __('Gekozen locatie'),
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];
        } elseif (filled($query)) {
            $results = $this->geocode($query);

            if (count($results) === 1) {
                $result = $results[0];

                if ($redirect = $this->redirectToKnownLocation((float) $result['latitude'], (float) $result['longitude'])) {
                    return $redirect;
                }

                $location = [
                    'name' => collect([$result['name'], $result['admin1'] ?? null, $result['country'] ?? null])->filter()->implode(', '),
                    'latitude' => (float) $result['latitude'],
                    'longitude' => (float) $result['longitude'],
                ];
            }
        }

        $forecast = $location ? Weather::forecast($location['latitude'], $location['longitude']) : null;

        return view('search', [
            'query' => $query,
            'results' => $results,
            'location' => $location,
            'current' => $forecast['current'] ?? null,
            'hours' => $forecast['hours'] ?? null,
            'days' => $forecast['days'] ?? null,
        ]);
    }

    protected function redirectToKnownLocation(float $latitude, float $longitude): ?RedirectResponse
    {
        $match = Location::nearestTo($latitude, $longitude);

        if (! $match) {
            return null;
        }

        return redirect()->route('location.show', [
            'country' => $match->country_slug,
            'state' => $match->state_slug,
            'city' => $match->city_slug,
        ]);
    }

    protected function geocode(string $query): array
    {
        $cacheKey = sprintf('geocode:search:%s', mb_strtolower($query));

        return Cache::remember($cacheKey, 60 * 60 * 24, function () use ($query) {
            $response = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $query,
                'count' => 8,
                'language' => 'nl',
                'format' => 'json',
            ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json('results') ?? [];
        });
    }
}
