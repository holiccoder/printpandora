<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $recentPosts = Post::with(['category', 'author'])
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get();

        return Inertia::render('home', [
            'recentPosts' => $recentPosts,
        ]);
    }
}
