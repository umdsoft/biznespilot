<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Display a listing of published blog posts.
     */
    public function index(Request $request): Response
    {
        $query = BlogPost::published()
            ->orderByDesc('published_at');

        // Filter by category
        if ($request->filled('category') && in_array($request->category, BlogPost::CATEGORIES)) {
            $query->category($request->category);
        }

        // Filter by locale
        if ($request->filled('locale') && in_array($request->locale, BlogPost::LOCALES)) {
            $query->locale($request->locale);
        }

        $posts = $query->paginate(12)->withQueryString();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => BlogPost::CATEGORIES,
            'filters' => [
                'category' => $request->category,
                'locale' => $request->locale,
            ],
        ]);
    }

    /**
     * Display the specified blog post.
     */
    public function show(BlogPost $blogPost): Response
    {
        // Only show published posts
        if (! $blogPost->is_published || $blogPost->published_at > now()) {
            abort(404);
        }

        // Increment views count
        $blogPost->increment('views_count');

        // Get related posts (same category, same locale, exclude current)
        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $blogPost->id)
            ->where('category', $blogPost->category)
            ->where('locale', $blogPost->locale)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => $blogPost,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
