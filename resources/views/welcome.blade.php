<?php

use App\Support\Weather;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;

$location = [
    'name' => 'Amsterdam',
    'latitude' => 52.3676,
    'longitude' => 4.9041,
];

$lat = request()->query('lat');
$lng = request()->query('lng');
$reset = request()->boolean('reset');

if ($reset) {
    Cookie::queue(Cookie::forget('location'));
} elseif (! is_numeric($lat) || ! is_numeric($lng)) {
    $cookieLocation = request()->cookie('location');

    if (is_string($cookieLocation) && str_contains($cookieLocation, ',')) {
        [$lat, $lng] = explode(',', $cookieLocation, 2);
    }
}

if (! $reset && is_numeric($lat) && is_numeric($lng) && abs($lat) <= 90 && abs($lng) <= 180) {
    $location = [
        'name' => __('Jouw locatie'),
        'latitude' => (float) $lat,
        'longitude' => (float) $lng,
    ];

    Cookie::queue('location', $lat.','.$lng, 60 * 24 * 30);

    $geocodeCacheKey = sprintf('geocode:reverse:%s,%s', $location['latitude'], $location['longitude']);

    $placeName = Cache::remember($geocodeCacheKey, 60 * 60 * 24 * 30, function () use ($location) {
        $response = Http::withHeaders([
            'User-Agent' => config('app.name').' ('.config('app.url').')',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $location['latitude'],
            'lon' => $location['longitude'],
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'zoom' => 10,
            'accept-language' => 'nl',
        ]);

        if ($response->failed()) {
            return null;
        }

        $address = $response->json('address') ?? [];

        return $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;
    });

    if (filled($placeName)) {
        $location['name'] = $placeName;
    }
}

$forecast = Weather::forecast($location['latitude'], $location['longitude']);

$hours = $forecast['hours'] ?? null;
$days = $forecast['days'] ?? null;

?>

<x-layouts::main :title="__('Weer Gids | Meer informatie over het weer')">
    <div class="mx-auto w-full max-w-4xl px-4 py-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Weer vandaag') }}</flux:heading>
                <flux:subheading>
                    {{ $location['name'] }} &middot; {{ now()->translatedFormat('l d F Y') }}
                </flux:subheading>
            </div>

            <div
                x-data="{
                    loading: false,
                    handleLocationError(error) {
                        loading = false;

                        const messages = {
                            1: '{{ __('Je hebt geen toestemming gegeven voor locatietoegang.') }}',
                            2: '{{ __('Je locatie kon niet worden bepaald.') }}',
                            3: '{{ __('Het ophalen van je locatie duurde te lang.') }}',
                        };

                        $flux.toast({
                            heading: '{{ __('Locatie niet beschikbaar') }}',
                            text: messages[error.code] ?? '{{ __('Er ging iets mis bij het ophalen van je locatie.') }}',
                            variant: 'warning',
                        });
                    },
                }"
                class="flex items-center gap-2"
            >
                @if (! $reset && (request()->has('lat') || request()->cookie('location')))
                    <flux:button variant="ghost" size="sm" href="{{ route('home', ['reset' => 1]) }}">
                        {{ __('Terug naar Amsterdam') }}
                    </flux:button>
                @endif

                <flux:button
                    variant="ghost"
                    size="sm"
                    icon="map-pin"
                    x-on:click="
                        loading = true;
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                window.location.href = '{{ route('home') }}?lat=' + position.coords.latitude + '&lng=' + position.coords.longitude;
                            },
                            handleLocationError
                        );
                    "
                    x-bind:disabled="loading"
                >
                    <span x-show="!loading">{{ __('Gebruik mijn locatie') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Locatie ophalen...') }}</span>
                </flux:button>
            </div>
        </div>

        @include('partials.forecast', ['location' => $location, 'hours' => $hours, 'days' => $days])
    </div>
</x-layouts::main>
