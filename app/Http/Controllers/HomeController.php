<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ProductImageResolver;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(ProductImageResolver $imageResolver): Response
    {
        $recentPosts = Post::with(['category', 'author'])
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get();

        $recentPosts->each(function (Post $post) use ($imageResolver): void {
            $featuredImage = $post->getRawOriginal('featured_image');

            if (is_string($featuredImage) && $featuredImage !== '') {
                $post->setAttribute('featured_image', $imageResolver->url($featuredImage));
            }
        });

        return Inertia::render('home', [
            'recentPosts' => $recentPosts,
        ]);
    }
}
