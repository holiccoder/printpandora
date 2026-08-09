<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ProductImageResolver;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(ProductImageResolver $imageResolver): Response
    {
        $posts = Post::with(['category', 'author'])
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->simplePaginate(12);

        $posts->getCollection()->each(
            fn (Post $post): Post => $this->resolveFeaturedImage($post, $imageResolver),
        );

        return Inertia::render('blog/index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug, ProductImageResolver $imageResolver): Response
    {
        $post = Post::with(['category', 'author'])
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Post::with(['category', 'author'])
            ->where('is_published', true)
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(3)
            ->get();

        $this->resolveFeaturedImage($post, $imageResolver);
        $related->each(
            fn (Post $relatedPost): Post => $this->resolveFeaturedImage($relatedPost, $imageResolver),
        );

        return Inertia::render('blog/show', [
            'post' => $post,
            'related' => $related,
        ]);
    }

    private function resolveFeaturedImage(Post $post, ProductImageResolver $imageResolver): Post
    {
        $featuredImage = $post->getRawOriginal('featured_image');

        if (is_string($featuredImage) && $featuredImage !== '') {
            $post->setAttribute('featured_image', $imageResolver->url($featuredImage));
        }

        return $post;
    }
}
