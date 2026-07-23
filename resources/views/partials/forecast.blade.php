@php
    use Illuminate\Support\Carbon;
    use App\Support\Weather;

    $weatherDescriptions = Weather::descriptions();
    $weatherIcons = Weather::icons();
@endphp

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
                <flux:text class="text-xs {{ Weather::uvCategory($hour['uv_index'])['class'] }}">
                    {{ __('UV') }} {{ round($hour['uv_index'], 1) }} &middot; {{ Weather::uvCategory($hour['uv_index'])['label'] }}
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
                    <flux:text class="text-xs {{ Weather::uvCategory($day['uv_index_max'])['class'] }}">
                        {{ __('UV') }} {{ round($day['uv_index_max'], 1) }} &middot; {{ Weather::uvCategory($day['uv_index_max'])['label'] }}
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
