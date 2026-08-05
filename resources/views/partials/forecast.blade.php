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
    @if ($current)
        <div class="mt-6 rounded-xl border border-hairline bg-white">
            <div class="px-5 pt-5 sm:px-6">
                <div class="text-xs tracking-[0.03em] text-slate uppercase">{{ $location['name'] }}</div>
                <div class="mt-0.5 font-data text-xs text-slate-light">{{ now()->format('d-m-Y') }}</div>
            </div>
            <div class="px-5 pt-4 pb-2 sm:px-6">
                <div class="flex items-start gap-4">
                    <flux:icon :icon="$weatherIcons[$current['weather_code']] ?? 'cloud'" class="mt-2 size-10 shrink-0 text-slate" />
                    <div class="font-data text-6xl leading-none font-medium text-ink sm:text-7xl">{{ round($current['temperature']) }}&deg;</div>
                </div>
                <div class="mt-2 text-sm text-slate">
                    {{ $weatherDescriptions[$current['weather_code']] ?? __('Onbekend') }}
                    &middot; {{ __('voelt als :temp°', ['temp' => round($current['apparent_temperature'])]) }}
                </div>
            </div>
        </div>
    @endif

    <div
        class="mt-6 flex gap-3 overflow-x-auto pb-2"
        x-data
        x-init="$nextTick(() => $el.querySelector('[data-current-hour]')?.scrollIntoView({ inline: 'center', block: 'nearest' }))"
    >
        @foreach ($hours as $hour)
            @php $isCurrentHour = Carbon::parse($hour['time'])->format('Y-m-d H') === Carbon::now('Europe/Amsterdam')->format('Y-m-d H'); @endphp
            <div
                @if ($isCurrentHour) data-current-hour @endif
                class="flex w-28 shrink-0 flex-col items-center gap-2 rounded-xl border bg-white p-4 text-center {{ $isCurrentHour ? 'border-accent-blue' : 'border-hairline' }}"
            >
                <flux:text class="font-data text-xs text-slate-light">{{ Carbon::parse($hour['time'])->format('H:i') }}</flux:text>
                <flux:icon :icon="$weatherIcons[$hour['weather_code']] ?? 'cloud'" class="size-8 text-slate" />
                <div class="font-data text-lg font-medium {{ $isCurrentHour ? 'text-accent-blue' : 'text-ink' }}">{{ round($hour['temperature']) }}&deg;</div>
                <flux:text class="text-xs text-slate">
                    {{ $weatherDescriptions[$hour['weather_code']] ?? __('Onbekend') }}
                </flux:text>
                <flux:text class="font-data text-xs text-accent-blue">
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
    <flux:heading size="lg" class="text-ink">{{ __('Regenradar') }}</flux:heading>
    <flux:subheading class="mb-3 text-slate">
        {{ $location['name'] }}
    </flux:subheading>

    <div class="overflow-hidden rounded-xl border border-hairline">
        <iframe
            src="https://www.rainviewer.com/map.html?loc={{ $location['latitude'] }},{{ $location['longitude'] }},7&oFa=0&oC=1&oU=0&oCS=1&oF=0&oAP=1&c=3&o=83&lm=1&layer=radar&sm=1&sn=1"
            title="{{ __('Regenradar') }} {{ $location['name'] }}"
            class="h-96 w-full border-0"
            loading="lazy"
        ></iframe>
    </div>
</div>

@if (! is_null($days))
    <div class="mt-8">
        <flux:heading size="lg" class="text-ink">{{ __('7-daagse verwachting') }}</flux:heading>
        <flux:subheading class="mb-3 text-slate">
            {{ $location['name'] }}
        </flux:subheading>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7">
            @foreach ($days as $day)
                @php $isToday = $day['date'] === Carbon::now('Europe/Amsterdam')->format('Y-m-d'); @endphp
                <div class="flex flex-col items-center gap-2 rounded-xl border bg-white p-4 text-center {{ $isToday ? 'border-accent-blue' : 'border-hairline' }}">
                    <flux:text class="text-xs text-slate-light uppercase">{{ Carbon::parse($day['date'])->translatedFormat('D d/m') }}</flux:text>
                    <flux:icon :icon="$weatherIcons[$day['weather_code']] ?? 'cloud'" class="size-8 text-slate" />
                    <div class="flex items-baseline gap-1 font-data">
                        <div class="text-lg font-medium {{ $isToday ? 'text-accent-blue' : 'text-ink' }}">{{ round($day['temperature_max']) }}&deg;</div>
                        <div class="text-xs text-slate-light">
                            {{ round($day['temperature_min']) }}&deg;
                        </div>
                    </div>
                    <flux:text class="text-xs text-slate">
                        {{ $weatherDescriptions[$day['weather_code']] ?? __('Onbekend') }}
                    </flux:text>
                    <flux:text class="font-data text-xs text-accent-blue">
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

<flux:text class="mt-8 text-center text-xs text-slate-light">
    {{ __('Weerdata door') }}
    <flux:link href="https://open-meteo.com/" target="_blank" class="text-xs text-slate">Open-Meteo.com</flux:link>
    &middot;
    {{ __('Regenradar door') }}
    <flux:link href="https://www.rainviewer.com/" target="_blank" class="text-xs text-slate">RainViewer.com</flux:link>
</flux:text>
