<x-layouts::main :title="__(':title | Blog | Weer Gids', ['title' => $post->title])">
    <div class="mx-auto w-full max-w-3xl px-4 py-8">
        <flux:link href="{{ route('blog.index') }}" wire:navigate class="text-sm">
            {{ __('← Alle berichten') }}
        </flux:link>

        <flux:heading size="xl" class="mt-4 text-ink">{{ $post->title }}</flux:heading>
        <flux:subheading class="font-data mb-6 text-slate-light">
            {{ $post->published_at->translatedFormat('d F Y') }} &middot; {{ $post->author->name }}
            @if ($post->categories->isNotEmpty())
                &middot; {{ $post->categories->pluck('name')->implode(', ') }}
            @endif
        </flux:subheading>

        @if ($post->featuredImageUrl())
            <img src="{{ $post->featuredImageUrl() }}" alt="" class="mb-6 w-full rounded-xl object-cover">
        @endif

        <div class="prose max-w-none prose-a:text-accent-blue prose-li:my-1 [&_li>p]:my-0">
            {!! $post->body !!}
        </div>

        @if ($relatedPosts->isNotEmpty())
            <div class="mt-12 border-t border-hairline pt-8">
                <flux:heading size="lg" class="mb-4 text-ink">{{ __('Gerelateerde berichten') }}</flux:heading>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedPosts as $relatedPost)
                        <flux:link
                            href="{{ route('blog.show', $relatedPost) }}"
                            wire:navigate
                            class="flex flex-col overflow-hidden rounded-xl border border-hairline bg-white !no-underline hover:bg-mist"
                        >
                            @if ($relatedPost->featuredImageUrl())
                                <img src="{{ $relatedPost->featuredImageUrl() }}" alt="" class="h-32 w-full object-cover">
                            @endif

                            <div class="flex flex-1 flex-col gap-1 p-3">
                                <flux:heading size="sm" class="text-ink">{{ $relatedPost->title }}</flux:heading>

                                <flux:text class="font-data mt-auto text-xs text-slate-light">
                                    {{ $relatedPost->published_at->translatedFormat('d F Y') }}
                                </flux:text>
                            </div>
                        </flux:link>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts::main>
