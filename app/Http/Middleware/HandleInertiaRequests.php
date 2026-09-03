<?php

namespace App\Http\Middleware;

use App\Models\Post;
use App\Services\Cart;
use App\Services\ProductImageResolver;
use App\Support\HardcodedContent;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Storefront content tree loaded from content/hardcoded-content.json.
            // Closure so the JSON parse only runs when Inertia actually merges
            // shared props — and the service memoises within the request.
            'content' => fn () => app(HardcodedContent::class)->all(),
            // Keep the global storefront drawer in sync with the session cart.
            'global_cart' => fn () => app(Cart::class)->drawerPayload(),
            // The Blog mega menu is rendered from the same latest published
            // posts that power the storefront's blog surfaces. Keep this
            // payload compact because it is shared with every Inertia page.
            'blog_dropdown_posts' => fn (): array => $this->blogDropdownPosts(),
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     title: string,
     *     slug: string,
     *     excerpt: string,
     *     featured_image: string|null
     * }>
     */
    private function blogDropdownPosts(): array
    {
        $imageResolver = app(ProductImageResolver::class);

        $posts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get();

        $dropdownPosts = [];

        foreach ($posts as $post) {
            $featuredImage = $post->getRawOriginal('featured_image');
            $featuredImageUrl = null;

            if (is_string($featuredImage) && $featuredImage !== '') {
                $resolvedImage = $imageResolver->url($featuredImage);
                $featuredImageUrl = is_string($resolvedImage) ? $resolvedImage : null;
            }

            $dropdownPosts[] = [
                'id' => (int) $post->getKey(),
                'title' => (string) $post->title,
                'slug' => (string) $post->slug,
                'excerpt' => $this->postExcerpt((string) $post->body),
                'featured_image' => $featuredImageUrl,
            ];
        }

        return $dropdownPosts;
    }

    private function postExcerpt(string $body, int $length = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');

        return mb_strlen($text) > $length
            ? mb_substr($text, 0, $length - 3).'...'
            : $text;
    }
}
