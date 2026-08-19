<x-layouts::main :title="__('Contact | Weer Gids')">
    <div class="mx-auto w-full max-w-4xl px-4 py-8">
        <flux:heading size="xl" class="text-ink">{{ __('Contact') }}</flux:heading>
        <flux:subheading class="mb-6 text-slate">
            {{ __('Heb je een vraag of opmerking? Laat het ons weten.') }}
        </flux:subheading>

        @if (session('status'))
            <flux:callout variant="success" class="mb-6" icon="check-circle" :heading="session('status')" />
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="flex max-w-lg flex-col gap-4">
            @csrf

            <flux:input
                name="name"
                :label="__('Naam')"
                :value="old('name')"
                required
            />
            <flux:error name="name" />

            <flux:input
                type="email"
                name="email"
                :label="__('E-mail')"
                :value="old('email')"
                required
            />
            <flux:error name="email" />

            <flux:textarea
                name="message"
                :label="__('Bericht')"
                rows="6"
                required
            >{{ old('message') }}</flux:textarea>
            <flux:error name="message" />

            @if (filled(config('services.turnstile.site_key')))
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                <flux:error name="cf-turnstile-response" />
            @endif

            <div>
                <flux:button type="submit" variant="primary">
                    {{ __('Versturen') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::main>
