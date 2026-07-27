<?php

use App\Models\BlogPost;

test('index only shows published posts', function () {
    $published = BlogPost::factory()->published()->create(['title' => 'Published post']);
    BlogPost::factory()->create(['title' => 'Draft post']);

    $response = $this->get(route('blog.index'));

    $response->assertOk();
    $response->assertSee('Published post');
    $response->assertDontSee('Draft post');
});

test('show displays a published post', function () {
    $post = BlogPost::factory()->published()->create(['title' => 'Hello world', 'body' => '<p>Hello content</p>']);

    $response = $this->get(route('blog.show', $post));

    $response->assertOk();
    $response->assertSee('Hello world');
    $response->assertSee('Hello content', false);
});

test('show 404s for an unpublished post', function () {
    $post = BlogPost::factory()->create();

    $response = $this->get(route('blog.show', $post));

    $response->assertNotFound();
});

test('show 404s for an unknown slug', function () {
    $response = $this->get('/blog/does-not-exist');

    $response->assertNotFound();
});
