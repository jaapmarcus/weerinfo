<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Blogbericht')] class extends Component {
    use WithFileUploads;

    public ?BlogPost $post = null;

    public string $title = '';
    public string $excerpt = '';
    public string $body = '';
    public array $categories = [];
    public string $newCategory = '';
    public bool $isPublished = false;
    public $featuredImage = null;

    public function mount(?BlogPost $post = null): void
    {
        $this->post = $post;

        if ($post) {
            $this->title = $post->title;
            $this->excerpt = (string) $post->excerpt;
            $this->body = $post->body;
            $this->isPublished = $post->is_published;
            $this->categories = $post->categories->pluck('name')->all();
        }
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return BlogCategory::query()->pluck('name')
            ->merge($this->categories)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function addNewCategory(): void
    {
        $name = trim($this->newCategory);

        if ($name !== '' && ! in_array($name, $this->categories, true)) {
            $this->categories[] = $name;
        }

        $this->newCategory = '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'featuredImage' => ['nullable', 'image', 'max:4096'],
        ]);

        $post = $this->post ?? new BlogPost(['user_id' => Auth::id()]);

        $post->title = $validated['title'];
        $post->excerpt = $validated['excerpt'];
        $post->body = $validated['body'];

        if ($this->isPublished && ! $post->is_published) {
            $post->published_at = now();
        }

        $post->is_published = $this->isPublished;

        if ($this->featuredImage) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }

            $post->featured_image = $this->featuredImage->store('blog', 'public');
        }

        $post->save();

        $post->categories()->sync(
            collect($this->categories)
                ->map(fn (string $name) => trim($name))
                ->filter()
                ->unique()
                ->map(fn (string $name) => BlogCategory::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name],
                ))
                ->pluck('id'),
        );

        Flux::toast(variant: 'success', text: __('Bericht opgeslagen.'));

        $this->redirect(route('blog.admin.index'), navigate: true);
    }
}; ?>

<section class="w-full max-w-3xl">
    <flux:heading size="xl">{{ $post ? __('Bericht bewerken') : __('Nieuw bericht') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Schrijf en publiceer een blogbericht') }}</flux:subheading>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="title" :label="__('Titel')" required autofocus />

        <flux:textarea wire:model="excerpt" :label="__('Samenvatting')" :description="__('Optioneel, wordt getoond in het overzicht')" rows="2" />

        <flux:pillbox
            variant="combobox"
            multiple
            wire:model="categories"
            :label="__('Categorieën')"
            :description="__('Kies een bestaande categorie of typ een nieuwe naam')"
            :placeholder="__('Selecteer of typ een nieuwe categorie...')"
        >
            <x-slot name="input">
                <flux:pillbox.input wire:model.live="newCategory" placeholder="{{ __('Selecteer of typ een nieuwe categorie...') }}" />
            </x-slot>

            @foreach ($this->categoryOptions as $option)
                <flux:pillbox.option value="{{ $option }}">{{ $option }}</flux:pillbox.option>
            @endforeach

            @if (trim($newCategory) !== '')
                <flux:pillbox.option.create wire:click="addNewCategory">
                    {{ __('Nieuw: ":name"', ['name' => trim($newCategory)]) }}
                </flux:pillbox.option.create>
            @endif
        </flux:pillbox>

        <flux:field>
            <flux:label>{{ __('Uitgelichte afbeelding') }}</flux:label>

            @if ($post?->featuredImageUrl() && ! $featuredImage)
                <img src="{{ $post->featuredImageUrl() }}" alt="" class="mb-2 h-32 rounded-lg object-cover">
            @endif

            <flux:file-upload wire:model="featuredImage" accept="image/*" />
            <flux:error name="featuredImage" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Inhoud') }}</flux:label>
            <flux:editor wire:model="body" />
            <flux:error name="body" />
        </flux:field>

        <flux:switch wire:model="isPublished" :label="__('Publiceren')" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Opslaan') }}</flux:button>
            <flux:button variant="filled" :href="route('blog.admin.index')" wire:navigate>{{ __('Annuleren') }}</flux:button>
        </div>
    </form>
</section>
