<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Support\Weather;

class LocationController extends Controller
{
    public function show(string $country, string $state, string $city)
    {
        $location = Location::where('country_slug', $country)
            ->where('state_slug', $state)
            ->where('city_slug', $city)
            ->firstOrFail();

        $forecast = Weather::forecast($location->latitude, $location->longitude);

        return view('location', [
            'location' => $location,
            'current' => $forecast['current'] ?? null,
            'hours' => $forecast['hours'] ?? null,
            'days' => $forecast['days'] ?? null,
        ]);
    }
}
