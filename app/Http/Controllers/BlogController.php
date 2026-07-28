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

        $categoryIds = $post->categories->pluck('id');

        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->with(['author', 'categories'])
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query->whereHas('categories', fn ($query) => $query->whereIn('blog_categories.id', $categoryIds))
            )
            ->latest('published_at')
            ->limit(4)
            ->get();

        if ($relatedPosts->count() < 4) {
            $excludedIds = $relatedPosts->pluck('id')->push($post->id);

            $relatedPosts = $relatedPosts->concat(
                BlogPost::published()
                    ->whereNotIn('id', $excludedIds)
                    ->with(['author', 'categories'])
                    ->latest('published_at')
                    ->limit(4 - $relatedPosts->count())
                    ->get()
            );
        }

        return view('blog.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
