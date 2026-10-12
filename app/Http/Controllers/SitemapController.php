<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\Post;
use App\Models\Product;
use App\Support\BusinessCardRoutes;
use App\Support\FlyersAndBrochuresProductCatalog;
use App\Support\PostcardProductCatalog;
use App\Support\StickerProductCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SitemapController extends Controller
{
    /** @var array<string, string|null> */
    private array $urls = [];

    public function __invoke(): Response
    {
        $this->urls = [];
        $baseUrl = rtrim((string) config('app.url', 'https://www.inkpavo.com'), '/');
        $staticRoutes = [
            'home',
            'shop.business-cards',
            'about',
            'terms',
            'privacy',
            'affiliate-program',
            'affiliate-terms',
            'shipping',
            'shipping.calculator',
            'help',
            'showcases',
            'blog.index',
            'business-card-design-service',
            'designer-partner-program',
            'shop.sample-packs',
            'shop.business-card-sample-pack',
            'shop.free-sample-pack',
            'contact.create',
        ];

        foreach ($staticRoutes as $routeName) {
            if (Route::has($routeName)) {
                $this->addUrl($baseUrl.route($routeName, [], false));
            }
        }

        Product::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use ($baseUrl): void {
                $path = $this->productPath($product);

                if ($path !== null) {
                    $this->addUrl($baseUrl.$path, $product->updated_at?->toDateString());
                }
            });

        Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get(['slug', 'updated_at'])
            ->each(fn (Post $post) => $this->addUrl($baseUrl.route('blog.show', ['slug' => $post->slug], false), $post->updated_at?->toDateString()));

        HelpCategory::query()
            ->where('is_active', true)
            ->get(['slug', 'updated_at'])
            ->each(fn (HelpCategory $category) => $this->addUrl($baseUrl.route('help.category', ['slug' => $category->slug], false), $category->updated_at?->toDateString()));

        HelpArticle::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get(['slug', 'updated_at'])
            ->each(fn (HelpArticle $article) => $this->addUrl($baseUrl.route('help.article', ['slug' => $article->slug], false), $article->updated_at?->toDateString()));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        ksort($this->urls);

        foreach ($this->urls as $loc => $lastModified) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.$this->escapeXml($loc)."</loc>\n";

            if ($lastModified !== null) {
                $xml .= '    <lastmod>'.$this->escapeXml($lastModified)."</lastmod>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function productPath(Product $product): ?string
    {
        $path = BusinessCardRoutes::pathForProductSlug($product->slug)
            ?? PostcardProductCatalog::pathForProductSlug($product->slug)
            ?? StickerProductCatalog::pathForProductSlug($product->slug)
            ?? FlyersAndBrochuresProductCatalog::pathForProductSlug($product->slug)
            ?? '/'.ltrim($product->slug, '/');

        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (Throwable) {
            return null;
        }

        return in_array($route->getName(), [
            'shop.show',
            'shop.business-card.show',
            'shop.postcard.show',
            'shop.sticker.show',
            'shop.flyer.show',
        ], true) ? $path : null;
    }

    private function addUrl(string $url, ?string $lastModified = null): void
    {
        $this->urls[$url] = $lastModified;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
