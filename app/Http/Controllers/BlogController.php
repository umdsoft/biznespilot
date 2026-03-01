<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Category SEO labels
     */
    protected const CATEGORY_META = [
        'crm' => ['title' => 'CRM tizimi haqida maqolalar', 'desc' => "CRM tizimi, mijozlar bazasi, sotuvlarni boshqarish haqida foydali maqolalar. O'zbekiston bizneslari uchun."],
        'marketing' => ['title' => 'Marketing strategiya va amaliyot', 'desc' => "Raqamli marketing, brending, reklama va biznes o'sish strategiyalari haqida maqolalar."],
        'smm' => ['title' => 'SMM va ijtimoiy tarmoqlar', 'desc' => "Instagram, Telegram, Facebook marketing, kontent yaratish va auditoriya o'stirish haqida."],
        'finance' => ['title' => 'Moliya va buxgalteriya', 'desc' => "Biznes moliyasi, hisobotlar, soliq va pul oqimini boshqarish haqida amaliy maslahatlar."],
        'hr' => ['title' => 'HR va jamoani boshqarish', 'desc' => "Xodimlarni boshqarish, yollash, motivatsiya va korporativ madaniyat haqida maqolalar."],
        'ai' => ['title' => "Sun'iy intellekt biznesda", 'desc' => "AI va sun'iy intellekt biznesda qo'llanilishi, avtomatlashtirish va innovatsiyalar haqida."],
        'business' => ['title' => 'Biznes boshqaruv', 'desc' => "Biznes strategiya, operatsion boshqaruv, o'sish va miqyoslash haqida foydali maqolalar."],
        'startup' => ['title' => 'Startup va tadbirkorlik', 'desc' => "Startup yaratish, investitsiya jalb qilish va tadbirkorlik haqida amaliy qo'llanma."],
    ];

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
        ])->withViewData([
            'seoTitle' => 'Biznes, marketing va CRM haqida blog | BiznesPilot',
            'seoDescription' => "CRM, marketing, SMM, moliya va biznes boshqaruvi haqida foydali maqolalar. O'zbekiston tadbirkorlari uchun amaliy maslahatlar.",
            'seoType' => 'blog',
        ]);
    }

    /**
     * Display blog posts filtered by category (SEO-friendly URL).
     */
    public function category(Request $request, string $category): Response
    {
        if (! in_array($category, BlogPost::CATEGORIES)) {
            abort(404);
        }

        $posts = BlogPost::published()
            ->category($category)
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $meta = self::CATEGORY_META[$category] ?? ['title' => ucfirst($category), 'desc' => ''];

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => BlogPost::CATEGORIES,
            'filters' => [
                'category' => $category,
                'locale' => $request->locale,
            ],
        ])->withViewData([
            'seoTitle' => $meta['title'] . ' | BiznesPilot Blog',
            'seoDescription' => $meta['desc'],
            'seoType' => 'blog',
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
        ])->withViewData([
            'seoTitle' => $blogPost->meta_title ?? ($blogPost->title . ' | BiznesPilot'),
            'seoDescription' => $blogPost->meta_description ?? $blogPost->excerpt,
            'seoImage' => $blogPost->cover_image,
            'seoType' => 'article',
        ]);
    }
}
