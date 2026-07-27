<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $category = request()->filled('category')
            ? BlogCategory::where('slug', request()->string('category'))->firstOrFail()
            : null;

        $posts = BlogPost::published()
            ->with(['author', 'categories'])
            ->when($category, fn ($query) => $query->whereRelation('categories', 'blog_categories.id', $category->id))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'category' => $category,
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->is_published && $post->published_at <= now(), 404);

        $post->load(['author', 'categories']);

        return view('blog.show', [
            'post' => $post,
        ]);
    }
}
