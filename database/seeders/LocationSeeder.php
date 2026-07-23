<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipalities = json_decode(
            file_get_contents(database_path('data/nl-municipalities.json')),
            true
        );

        foreach ($municipalities as $municipality) {
            Location::updateOrCreate(
                [
                    'country_slug' => Str::slug($municipality['country']),
                    'state_slug' => Str::slug($municipality['state']),
                    'city_slug' => Str::slug($municipality['city']),
                ],
                [
                    'country' => $municipality['country'],
                    'state' => $municipality['state'],
                    'city' => $municipality['city'],
                    'latitude' => $municipality['latitude'],
                    'longitude' => $municipality['longitude'],
                ]
            );
        }
    }
}
