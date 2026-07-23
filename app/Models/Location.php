<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'country',
        'country_slug',
        'state',
        'state_slug',
        'city',
        'city_slug',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Find the closest seeded location to a coordinate, e.g. to match a
     * geocoding search result against one of our SEO location pages. Names
     * can't be trusted for this (sources disagree, e.g. Open-Meteo's
     * "Friesland" vs PDOK's official "Fryslân"), so this matches by distance.
     */
    public static function nearestTo(float $latitude, float $longitude, float $maxDistanceKm = 10): ?self
    {
        return static::all()
            ->sortBy(fn (self $location) => self::haversineKm($latitude, $longitude, $location->latitude, $location->longitude))
            ->first(fn (self $location) => self::haversineKm($latitude, $longitude, $location->latitude, $location->longitude) <= $maxDistanceKm);
    }

    protected static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
