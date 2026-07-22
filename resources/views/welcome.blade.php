<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

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

$weatherDescriptions = [
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

$weatherIcons = [
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

$uvIndexCategory = function (?float $uvIndex): array {
    return match (true) {
        $uvIndex === null => ['label' => __('Onbekend'), 'class' => 'text-neutral-500 dark:text-neutral-400'],
        $uvIndex < 3 => ['label' => __('Laag'), 'class' => 'text-emerald-600 dark:text-emerald-400'],
        $uvIndex < 6 => ['label' => __('Matig'), 'class' => 'text-yellow-600 dark:text-yellow-400'],
        $uvIndex < 8 => ['label' => __('Hoog'), 'class' => 'text-orange-600 dark:text-orange-400'],
        $uvIndex < 11 => ['label' => __('Zeer hoog'), 'class' => 'text-red-600 dark:text-red-400'],
        default => ['label' => __('Extreem'), 'class' => 'text-purple-600 dark:text-purple-400'],
    };
};

$cacheKey = sprintf('weather:forecast:%s,%s:%s', $location['latitude'], $location['longitude'], now()->format('Y-m-d-H'));

$forecast = Cache::remember($cacheKey, 900, function () use ($location) {
    $response = Http::get('https://api.open-meteo.com/v1/forecast', [
        'latitude' => $location['latitude'],
        'longitude' => $location['longitude'],
        'hourly' => 'temperature_2m,precipitation_probability,weather_code,uv_index',
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,uv_index_max',
        'timezone' => 'Europe/Amsterdam',
        'forecast_days' => 7,
    ]);

    if ($response->failed()) {
        return null;
    }

    $hourly = $response->json('hourly');
    $today = array_slice($hourly['time'], 0, 24);

    $daily = $response->json('daily');

    return [
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

        @if (is_null($hours))
            <flux:callout variant="danger" class="mt-6" icon="exclamation-triangle" :heading="__('Weerdata kon niet worden opgehaald')">
                {{ __('Probeer het later opnieuw.') }}
            </flux:callout>
        @else
            <div
                class="mt-6 flex gap-3 overflow-x-auto pb-2"
                x-data
                x-init="$nextTick(() => $el.querySelector('[data-current-hour]')?.scrollIntoView({ inline: 'center', block: 'nearest' }))"
            >
                @foreach ($hours as $hour)
                    @php $isCurrentHour = Carbon::parse($hour['time'])->format('Y-m-d H') === Carbon::now('Europe/Amsterdam')->format('Y-m-d H'); @endphp
                    <div
                        @if ($isCurrentHour) data-current-hour @endif
                        class="flex w-28 shrink-0 flex-col items-center gap-2 rounded-xl border p-4 text-center {{ $isCurrentHour ? 'border-blue-500 dark:border-blue-400' : 'border-neutral-200 dark:border-neutral-700' }}"
                    >
                        <flux:text class="font-medium">{{ Carbon::parse($hour['time'])->format('H:i') }}</flux:text>
                        <flux:icon :icon="$weatherIcons[$hour['weather_code']] ?? 'cloud'" class="size-8 text-neutral-500 dark:text-neutral-400" />
                        <flux:heading size="lg">{{ round($hour['temperature']) }}&deg;</flux:heading>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $weatherDescriptions[$hour['weather_code']] ?? __('Onbekend') }}
                        </flux:text>
                        <flux:text class="text-xs text-blue-500">
                            {{ $hour['precipitation_probability'] }}% {{ __('neerslag') }}
                        </flux:text>
                        <flux:text class="text-xs {{ $uvIndexCategory($hour['uv_index'])['class'] }}">
                            {{ __('UV') }} {{ round($hour['uv_index'], 1) }} &middot; {{ $uvIndexCategory($hour['uv_index'])['label'] }}
                        </flux:text>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8">
            <flux:heading size="lg">{{ __('Regenradar') }}</flux:heading>
            <flux:subheading class="mb-3">
                {{ $location['name'] }}
            </flux:subheading>

            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <iframe
                    src="https://www.rainviewer.com/map.html?loc={{ $location['latitude'] }},{{ $location['longitude'] }},7&oFa=0&oC=1&oU=0&oCS=1&oF=0&oAP=1&c=3&o=83&lm=1&layer=radar&sm=1&sn=1"
                    class="h-96 w-full"
                    frameborder="0"
                    loading="lazy"
                ></iframe>
            </div>
        </div>

        @if (! is_null($days))
            <div class="mt-8">
                <flux:heading size="lg">{{ __('7-daagse verwachting') }}</flux:heading>
                <flux:subheading class="mb-3">
                    {{ $location['name'] }}
                </flux:subheading>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7">
                    @foreach ($days as $day)
                        <div class="flex flex-col items-center gap-2 rounded-xl border border-neutral-200 p-4 text-center dark:border-neutral-700">
                            <flux:text class="font-medium">{{ Carbon::parse($day['date'])->translatedFormat('D d/m') }}</flux:text>
                            <flux:icon :icon="$weatherIcons[$day['weather_code']] ?? 'cloud'" class="size-8 text-neutral-500 dark:text-neutral-400" />
                            <div class="flex items-baseline gap-1">
                                <flux:heading size="lg">{{ round($day['temperature_max']) }}&deg;</flux:heading>
                                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ round($day['temperature_min']) }}&deg;
                                </flux:text>
                            </div>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $weatherDescriptions[$day['weather_code']] ?? __('Onbekend') }}
                            </flux:text>
                            <flux:text class="text-xs text-blue-500">
                                {{ $day['precipitation_probability_max'] }}% {{ __('neerslag') }}
                            </flux:text>
                            <flux:text class="text-xs {{ $uvIndexCategory($day['uv_index_max'])['class'] }}">
                                {{ __('UV') }} {{ round($day['uv_index_max'], 1) }} &middot; {{ $uvIndexCategory($day['uv_index_max'])['label'] }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <flux:text class="mt-8 text-center text-xs text-neutral-500 dark:text-neutral-400">
            {{ __('Weerdata door') }}
            <flux:link href="https://open-meteo.com/" target="_blank" class="text-xs">Open-Meteo.com</flux:link>
            &middot;
            {{ __('Regenradar door') }}
            <flux:link href="https://www.rainviewer.com/" target="_blank" class="text-xs">RainViewer.com</flux:link>
        </flux:text>
    </div>
</x-layouts::main>
