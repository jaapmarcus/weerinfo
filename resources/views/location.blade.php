<x-layouts::main :title="__(':city (:state) | Weer Gids', ['city' => $location->city, 'state' => $location->state])">
    <div class="mx-auto w-full max-w-4xl px-4 py-8">
        <flux:subheading>
            <flux:link href="{{ route('home') }}" wire:navigate>{{ __('Weer Gids') }}</flux:link>
            &middot; {{ $location->country }} &middot; {{ $location->state }}
        </flux:subheading>

        <flux:heading size="xl">{{ __('Weer in :city', ['city' => $location->city]) }}</flux:heading>
        <flux:subheading class="mb-3">
            {{ now()->translatedFormat('l d F Y') }}
        </flux:subheading>

        @include('partials.forecast', [
            'location' => ['name' => $location->city, 'latitude' => $location->latitude, 'longitude' => $location->longitude],
            'hours' => $hours,
            'days' => $days,
        ])
    </div>
</x-layouts::main>
