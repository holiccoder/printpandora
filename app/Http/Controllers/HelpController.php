<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HelpController extends Controller
{
    public function index(): \Inertia\Response
    {
        $categories = HelpCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount(['publishedArticles'])
            ->get();

        $faqs = Faq::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->latest()
            ->limit(12)
            ->get();

        return Inertia::render('help/index', [
            'categories' => $categories,
            'faqs' => $faqs,
        ]);
    }

    public function category(string $slug): \Inertia\Response
    {
        $category = HelpCategory::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $articles = $category->publishedArticles()
            ->select(['id', 'category_id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'sort_order'])
            ->get();

        $faqs = $category->faqs()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return Inertia::render('help/category', [
            'category' => $category,
            'articles' => $articles,
            'faqs' => $faqs,
        ]);
    }

    public function article(string $slug): \Inertia\Response
    {
        $article = HelpArticle::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('slug', $slug)
            ->firstOrFail();

        $article->load('category');

        $related = HelpArticle::query()
            ->where('is_published', true)
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('sort_order')
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'category_id', 'title', 'slug', 'excerpt', 'published_at']);

        return Inertia::render('help/article', [
            'article' => $article,
            'related' => $related,
        ]);
    }
}
