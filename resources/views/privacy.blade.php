<x-layouts::main :title="__('Privacybeleid | Weer Gids')">
    <div class="mx-auto w-full max-w-4xl px-4 py-8">
        <flux:heading size="xl" class="text-ink">{{ __('Privacybeleid') }}</flux:heading>
        <flux:subheading class="mb-6 text-slate">
            {{ __('Laatst bijgewerkt: :date', ['date' => now()->translatedFormat('d F Y')]) }}
        </flux:subheading>

        <div class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg" class="text-ink">{{ __('Welke gegevens verzamelen we') }}</flux:heading>
                <flux:text class="mt-2 text-slate">
                    {{ __('Wanneer je het contactformulier gebruikt, verwerken we de naam, het e-mailadres en het bericht die je invult, uitsluitend om je vraag te kunnen beantwoorden. Wanneer je een plaats opzoekt, sturen we die zoekopdracht door naar onze geocoding-provider om de locatie te vinden; deze zoekopdracht wordt niet aan jou gekoppeld opgeslagen.') }}
                </flux:text>
            </div>

            <div>
                <flux:heading size="lg" class="text-ink">{{ __('Analytics en cookies') }}</flux:heading>
                <flux:text class="mt-2 text-slate">
                    {{ __('We gebruiken Matomo Analytics om te zien hoe de website wordt gebruikt, zodat we deze kunnen verbeteren. Dit kan met cookies gebeuren. We gebruiken geen advertentiecookies en verkopen geen gegevens aan derden.') }}
                </flux:text>
            </div>

            <div>
                <flux:heading size="lg" class="text-ink">{{ __('Delen met derden') }}</flux:heading>
                <flux:text class="mt-2 text-slate">
                    {{ __('Voor het opzoeken van plaatsen en het weerbericht maken we gebruik van de diensten van Open-Meteo. Op het contactformulier kan Cloudflare Turnstile worden ingezet om spam te voorkomen. Deze partijen ontvangen alleen de gegevens die nodig zijn om hun dienst te leveren.') }}
                </flux:text>
            </div>

            <div>
                <flux:heading size="lg" class="text-ink">{{ __('Bewaartermijn') }}</flux:heading>
                <flux:text class="mt-2 text-slate">
                    {{ __('Berichten via het contactformulier bewaren we niet langer dan nodig is om je vraag af te handelen.') }}
                </flux:text>
            </div>

            <div>
                <flux:heading size="lg" class="text-ink">{{ __('Jouw rechten') }}</flux:heading>
                <flux:text class="mt-2 text-slate">
                    {{ __('Je hebt het recht om je gegevens in te zien, te laten corrigeren of te laten verwijderen. Neem hiervoor contact met ons op via het') }}
                    <flux:link href="{{ route('contact.index') }}" wire:navigate>{{ __('contactformulier') }}</flux:link>.
                    {{ __('Ook heb je het recht om een klacht in te dienen bij de Autoriteit Persoonsgegevens.') }}
                </flux:text>
            </div>
        </div>
    </div>
</x-layouts::main>
