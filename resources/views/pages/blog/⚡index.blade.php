<?php

use App\Models\BlogPost;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Blog beheer')] class extends Component {
    use WithPagination;

    #[Computed]
    public function posts()
    {
        return BlogPost::with(['author', 'categories'])
            ->latest()
            ->paginate(15);
    }

    public function togglePublish(BlogPost $post): void
    {
        $post->is_published = ! $post->is_published;

        if ($post->is_published && ! $post->published_at) {
            $post->published_at = now();
        }

        $post->save();

        Flux::toast(variant: 'success', text: $post->is_published ? __('Bericht gepubliceerd.') : __('Bericht is nu een concept.'));
    }

    public function delete(BlogPost $post): void
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        Flux::toast(variant: 'success', text: __('Bericht verwijderd.'));
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Blog beheer') }}</flux:heading>
            <flux:subheading>{{ __('Beheer alle blogberichten') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('blog.admin.create')" wire:navigate>
            {{ __('Nieuw bericht') }}
        </flux:button>
    </div>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Titel') }}</flux:table.column>
            <flux:table.column>{{ __('Auteur') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Acties') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->posts as $post)
                <flux:table.row :key="$post->id">
                    <flux:table.cell variant="strong">{{ $post->title }}</flux:table.cell>
                    <flux:table.cell>{{ $post->author->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :variant="$post->is_published ? 'solid' : 'pill'" :color="$post->is_published ? 'lime' : 'zinc'" size="sm">
                            {{ $post->is_published ? __('Gepubliceerd') : __('Concept') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" wire:click="togglePublish('{{ $post->slug }}')">
                                {{ $post->is_published ? __('Verbergen') : __('Publiceren') }}
                            </flux:button>

                            <flux:button size="sm" :href="route('blog.admin.edit', $post)" wire:navigate>
                                {{ __('Bewerken') }}
                            </flux:button>

                            <flux:modal.trigger name="delete-post-{{ $post->id }}">
                                <flux:button size="sm" variant="danger">{{ __('Verwijderen') }}</flux:button>
                            </flux:modal.trigger>

                            <flux:modal name="delete-post-{{ $post->id }}" class="max-w-md">
                                <div class="space-y-6">
                                    <flux:heading size="lg">{{ __('Bericht verwijderen?') }}</flux:heading>
                                    <flux:subheading>
                                        {{ __('":title" wordt permanent verwijderd. Dit kan niet ongedaan worden gemaakt.', ['title' => $post->title]) }}
                                    </flux:subheading>

                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="filled">{{ __('Annuleren') }}</flux:button>
                                        </flux:modal.close>

                                        <flux:button variant="danger" wire:click="delete('{{ $post->slug }}')">
                                            {{ __('Verwijderen') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('Er zijn nog geen berichten.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $this->posts->links() }}
    </div>
</section>
