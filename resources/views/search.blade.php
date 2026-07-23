<x-layouts::main :title="__('Zoek een plaats | Weer Gids')">
    <div class="mx-auto w-full max-w-4xl px-4 py-8">
        <flux:heading size="xl">{{ __('Zoek een plaats') }}</flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Bekijk het weer voor elke plaats ter wereld') }}
        </flux:subheading>

        <form method="GET" action="{{ route('search') }}" class="flex items-end gap-3">
            <flux:input
                name="q"
                :label="__('Plaatsnaam')"
                :value="$query"
                placeholder="{{ __('Bijv. Utrecht') }}"
                autofocus
                class="max-w-sm"
            />
            <flux:button type="submit" variant="primary">
                {{ __('Zoeken') }}
            </flux:button>
        </form>

        @if (filled($query) && empty($results) && is_null($location))
            <flux:callout variant="warning" class="mt-6" icon="magnifying-glass" :heading="__('Geen plaats gevonden')">
                {{ __('Er is geen plaats gevonden voor ":query". Probeer een andere schrijfwijze.', ['query' => $query]) }}
            </flux:callout>
        @endif

        @if (count($results) > 1)
            <div class="mt-6 flex flex-col gap-2">
                @foreach ($results as $result)
                    <flux:link
                        href="{{ route('search', ['q' => $query, 'lat' => $result['latitude'], 'lng' => $result['longitude'], 'name' => collect([$result['name'], $result['admin1'] ?? null, $result['country'] ?? null])->filter()->implode(', ')]) }}"
                        class="rounded-xl border border-neutral-200 p-4 no-underline hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800"
                    >
                        <flux:text class="font-medium text-zinc-800 dark:text-white">{{ $result['name'] }}</flux:text>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ collect([$result['admin1'] ?? null, $result['country'] ?? null])->filter()->implode(', ') }}
                        </flux:text>
                    </flux:link>
                @endforeach
            </div>
        @endif

        @if ($location)
            <div class="mt-8">
                <flux:heading size="lg">{{ $location['name'] }}</flux:heading>
                <flux:subheading class="mb-3">
                    {{ now()->translatedFormat('l d F Y') }}
                </flux:subheading>

                @include('partials.forecast', ['location' => $location, 'hours' => $hours, 'days' => $days])
            </div>
        @endif
    </div>
</x-layouts::main>
