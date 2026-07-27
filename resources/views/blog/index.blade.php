<x-layouts::main :title="__(':category | Blog | Weer Gids', ['category' => $category?->name ?? __('Blog')])">
    <div class="mx-auto w-full max-w-4xl px-4 py-8">
        <flux:heading size="xl" class="text-ink">{{ __('Blog') }}</flux:heading>
        <flux:subheading class="mb-6 text-slate">
            {{ $category ? $category->name : __('Nieuws en artikelen van Weer Gids') }}
        </flux:subheading>

        @if ($category)
            <flux:link href="{{ route('blog.index') }}" wire:navigate class="text-sm">
                {{ __('← Alle berichten') }}
            </flux:link>
        @endif

        @if ($posts->isEmpty())
            <flux:text class="mt-6 text-slate">{{ __('Er zijn nog geen berichten.') }}</flux:text>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                @foreach ($posts as $post)
                    <flux:link
                        href="{{ route('blog.show', $post) }}"
                        wire:navigate
                        class="flex flex-col overflow-hidden rounded-xl border border-hairline bg-white !no-underline hover:bg-mist"
                    >
                        @if ($post->featuredImageUrl())
                            <img src="{{ $post->featuredImageUrl() }}" alt="" class="h-40 w-full object-cover">
                        @endif

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            @if ($post->categories->isNotEmpty())
                                <flux:text class="text-xs text-slate">
                                    {{ $post->categories->pluck('name')->implode(', ') }}
                                </flux:text>
                            @endif

                            <flux:heading size="lg" class="text-ink">{{ $post->title }}</flux:heading>

                            @if ($post->excerpt)
                                <flux:text class="text-sm text-slate">{{ $post->excerpt }}</flux:text>
                            @endif

                            <flux:text class="font-data mt-auto text-xs text-slate-light">
                                {{ $post->published_at->translatedFormat('d F Y') }} &middot; {{ $post->author->name }}
                            </flux:text>
                        </div>
                    </flux:link>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</x-layouts::main>
