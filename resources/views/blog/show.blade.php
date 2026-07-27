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

        <div class="prose max-w-none prose-a:text-accent-blue">
            {!! $post->body !!}
        </div>
    </div>
</x-layouts::main>
