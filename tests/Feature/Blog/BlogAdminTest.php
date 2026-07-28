<?php

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected away from the blog admin', function () {
    $this->get(route('blog.admin.index'))->assertRedirect(route('login'));
    $this->get(route('blog.admin.create'))->assertRedirect(route('login'));
});

test('authenticated users can create a blog post', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::blog.form')
        ->set('title', 'My first post')
        ->set('excerpt', 'A short excerpt')
        ->set('body', '<p>Post body</p>')
        ->set('categories', ['Weer', 'Nederland'])
        ->set('isPublished', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('blog.admin.index'));

    $post = BlogPost::firstWhere('title', 'My first post');

    expect($post)->not->toBeNull();
    expect($post->slug)->toBe('my-first-post');
    expect($post->is_published)->toBeTrue();
    expect($post->published_at)->not->toBeNull();
    expect($post->categories->pluck('name')->all())->toEqual(['Weer', 'Nederland']);
});

test('a title is required to save a post', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::blog.form')
        ->set('body', '<p>Post body</p>')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('authenticated users can edit any blog post', function () {
    $author = User::factory()->create();
    $editor = User::factory()->create();
    $post = BlogPost::factory()->create(['user_id' => $author->id, 'title' => 'Original title']);

    $this->actingAs($editor);

    Livewire::test('pages::blog.form', ['post' => $post])
        ->set('title', 'Updated title')
        ->set('body', '<p>Updated body</p>')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->fresh()->title)->toBe('Updated title');
    expect($post->fresh()->slug)->toBe('original-title');
});

test('a featured image can be uploaded and replaced', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::blog.form')
        ->set('title', 'Post with image')
        ->set('body', '<p>Body</p>')
        ->set('featuredImage', UploadedFile::fake()->image('cover.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $post = BlogPost::firstWhere('title', 'Post with image');

    expect($post->featured_image)->not->toBeNull();
    Storage::disk('public')->assertExists($post->featured_image);
});

test('body html is sanitized on save', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::blog.form')
        ->set('title', 'Sanitized post')
        ->set('body', '<p>Safe</p><script>alert(1)</script>')
        ->call('save')
        ->assertHasNoErrors();

    $post = BlogPost::firstWhere('title', 'Sanitized post');

    expect($post->body)->not->toContain('<script>');
});

test('authenticated users can toggle publish state and delete a post', function () {
    $post = BlogPost::factory()->create();

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::blog.index')
        ->call('togglePublish', $post->slug)
        ->assertHasNoErrors();

    expect($post->fresh()->is_published)->toBeTrue();

    Livewire::test('pages::blog.index')
        ->call('delete', $post->slug);

    expect(BlogPost::find($post->id))->toBeNull();
});
