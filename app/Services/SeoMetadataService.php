<?php

namespace App\Services;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\Post;
use App\Models\Product;
use App\Support\BusinessCardRoutes;
use App\Support\FlyersAndBrochuresProductCatalog;
use App\Support\HardcodedContent;
use App\Support\PostcardProductCatalog;
use App\Support\StickerProductCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SeoMetadataService
{
    /** @return array<string, mixed> */
    public function forRequest(Request $request): array
    {
        $appName = (string) config('app.name', 'InkPavo');
        $baseUrl = rtrim((string) config('app.url', 'https://www.inkpavo.com'), '/');
        $canonicalPath = $request->getPathInfo();
        $metadata = [
            'title' => $appName,
            'description' => 'InkPavo account and order information.',
            'robots' => 'noindex,follow',
            'type' => 'website',
            'image' => null,
            'published_at' => null,
            'modified_at' => null,
            'author' => null,
            'schema' => null,
            'is_authoritative' => false,
        ];

        $content = app(HardcodedContent::class)->all();
        $routeName = (string) ($request->route()?->getName() ?? '');

        if (in_array($routeName, [
            'shop.business-card.show',
            'shop.postcard.show',
            'shop.sticker.show',
            'shop.flyer.show',
            'shop.show',
        ], true)) {
            $metadata = $this->productMetadata($request, $routeName, $metadata);
            $canonicalPath = $metadata['canonical_path'] ?? $canonicalPath;
            unset($metadata['canonical_path']);
        } elseif ($routeName === 'blog.show') {
            $post = Post::query()
                ->with('author')
                ->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where('slug', $request->route('slug'))
                ->first();

            if ($post) {
                $description = $this->description($post->body);
                $image = $this->imageUrl($post->featured_image, $baseUrl);
                $publishedAt = $post->published_at?->toIso8601String();
                $modifiedAt = $post->updated_at?->toIso8601String();
                $metadata = [
                    'title' => (string) $post->title,
                    'description' => $description,
                    'robots' => 'index,follow',
                    'type' => 'article',
                    'image' => $image,
                    'published_at' => $publishedAt,
                    'modified_at' => $modifiedAt,
                    'author' => $post->author?->name,
                    'schema' => array_filter([
                        '@type' => 'BlogPosting',
                        'headline' => $post->title,
                        'description' => $description,
                        'datePublished' => $publishedAt,
                        'dateModified' => $modifiedAt,
                        'author' => $post->author
                            ? ['@type' => 'Person', 'name' => $post->author->name]
                            : ['@type' => 'Organization', 'name' => $appName],
                        'image' => $image,
                        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $baseUrl.$canonicalPath],
                    ], static fn (mixed $value): bool => $value !== null),
                ];
            }
        } elseif ($routeName === 'help.article') {
            $article = HelpArticle::query()
                ->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where('slug', $request->route('slug'))
                ->first();

            if ($article) {
                $metadata = [
                    'title' => $article->title.' - Help Center',
                    'description' => $this->description($article->excerpt ?: $article->body),
                    'robots' => 'index,follow',
                    'type' => 'article',
                    'image' => $this->imageUrl($article->featured_image, $baseUrl),
                    'published_at' => $article->published_at?->toIso8601String(),
                    'modified_at' => $article->updated_at?->toIso8601String(),
                    'author' => null,
                    'schema' => [
                        '@type' => 'Article',
                        'headline' => $article->title,
                        'description' => $this->description($article->excerpt ?: $article->body),
                        'datePublished' => $article->published_at?->toIso8601String(),
                        'dateModified' => $article->updated_at?->toIso8601String(),
                        'image' => $this->imageUrl($article->featured_image, $baseUrl),
                        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $baseUrl.$canonicalPath],
                    ],
                ];
            }
        } elseif ($routeName === 'help.category') {
            $category = HelpCategory::query()
                ->where('is_active', true)
                ->where('slug', $request->route('slug'))
                ->first();

            if ($category) {
                $metadata = [
                    'title' => $category->name.' - Help Center',
                    'description' => $this->description($category->description ?: 'Browse help articles and answers about '.$category->name.'.'),
                    'robots' => 'index,follow',
                    'type' => 'website',
                    'image' => null,
                ];
            }
        } elseif ($routeName === 'home') {
            $metadata = $this->contentMetadata($content, 'home_page', 'Custom Printing for Businesses', 'Order custom business cards, stickers, postcards, and more from InkPavo.', 'seo.page_title', 'seo.page_description');
        } elseif ($routeName === 'blog.index') {
            $metadata = $this->contentMetadata($content, 'blog_index_page', 'Print & Design Blog', 'Explore print, design, and branding ideas from InkPavo.');
        } elseif ($routeName === 'shop.business-cards') {
            $metadata = $this->contentMetadata($content, 'business_cards_landing_page', 'Custom Business Cards', 'Explore custom business cards in distinctive stocks, sizes, and finishes.');
        } elseif ($routeName === 'business-card-design-service') {
            $metadata = $this->contentMetadata($content, 'design_service_page', 'Business Card Design Services', 'Get professional business card design, layout, or print-ready file support from InkPavo.');
        } elseif ($routeName === 'designer-partner-program') {
            $metadata = $this->contentMetadata($content, 'designer_partner_page', 'Designer Partner Program', 'Join InkPavo’s designer partner program for print production support and partner benefits.');
        } elseif ($routeName === 'affiliate-program') {
            $metadata = $this->contentMetadata($content, 'affiliate_program_page', 'InkPavo Affiliate Program', 'Join the InkPavo affiliate program and earn commission on referred orders.');
        } elseif ($routeName === 'affiliate-terms') {
            $metadata = $this->contentMetadata($content, 'affiliate_terms_page', 'Affiliate Program Terms & Conditions', 'Read the terms and conditions for the InkPavo Affiliate Program.');
        } elseif ($routeName === 'help') {
            $metadata = $this->contentMetadata($content, 'help_center_page', 'InkPavo Help Center', 'Find answers about orders, shipping, artwork, and print finishes.');
        } elseif ($routeName === 'about') {
            $metadata = $this->contentMetadata($content, 'about_page', 'About InkPavo', 'Learn about InkPavo and our custom printing services.');
        } elseif ($routeName === 'terms') {
            $metadata = $this->contentMetadata($content, 'terms_page', 'Terms & Conditions', 'Read the terms and conditions for using InkPavo.');
        } elseif ($routeName === 'privacy') {
            $metadata = $this->contentMetadata($content, 'privacy_page', 'Privacy Policy', 'Learn how InkPavo collects, uses, and protects your personal information.');
        } elseif ($routeName === 'shipping') {
            $metadata = $this->pageMetadata('Shipping & Delivery', 'Production times, shipping methods, carriers, costs, and what to do if something goes wrong.');
        } elseif ($routeName === 'shipping.calculator') {
            $metadata = $this->pageMetadata('Shipping Calculator', 'Estimate shipping costs and delivery times for InkPavo orders.');
        } elseif ($routeName === 'shop.sample-packs') {
            $metadata = $this->pageMetadata('Free Sample Packs', 'Get a feel for the full InkPavo range with a free sample pack — papers, finishes, cards and stickers, posted to your door.');
        } elseif ($routeName === 'shop.business-card-sample-pack') {
            $metadata = $this->pageMetadata('Free Business Card Sample Pack', 'Order a free InkPavo Business Card Sample Pack. Feel every paper stock, finish and size before you buy — shipping included.');
        } elseif ($routeName === 'shop.free-sample-pack') {
            $metadata = $this->pageMetadata('Free Business Card Sample Pack', 'Experience the quality of InkPavo before you buy. Order a free business card sample pack featuring our paper stocks, weights, and premium finishes — free worldwide shipping included.');
        } elseif ($routeName === 'contact.create') {
            $metadata = $this->pageMetadata('Contact us', "Get in touch with InkPavo. Questions about an order, a product, or a partnership? We'd love to hear from you.");
        } elseif ($routeName === 'showcases') {
            $metadata = $this->pageMetadata('Showcases', 'Explore our premium paper and print finishing showcase.');
        }

        return [
            ...$metadata,
            'is_authoritative' => ($metadata['robots'] ?? null) === 'index,follow',
            'full_title' => $this->fullTitle((string) $metadata['title'], $appName),
            'canonical' => $baseUrl.$canonicalPath,
        ];
    }

    /** @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    private function contentMetadata(
        array $content,
        string $section,
        string $fallbackTitle,
        string $fallbackDescription,
        string $titleKey = 'seo.title',
        string $descriptionKey = 'seo.description',
    ): array {
        $title = Arr::get($content, $section.'.'.$titleKey)
            ?? Arr::get($content, $section.'.title')
            ?? $fallbackTitle;
        $description = Arr::get($content, $section.'.'.$descriptionKey)
            ?? Arr::get($content, $section.'.description')
            ?? $fallbackDescription;

        $imagePath = Arr::get($content, $section.'.seo.image')
            ?? Arr::get($content, $section.'.hero.image_url');

        return $this->pageMetadata(
            (string) $title,
            (string) $description,
            'website',
            is_string($imagePath) ? $this->imageUrl($imagePath, rtrim((string) config('app.url'), '/')) : null,
        );
    }

    /** @return array<string, mixed> */
    private function pageMetadata(string $title, string $description, string $type = 'website', ?string $image = null): array
    {
        return [
            'title' => $title,
            'description' => $this->description($description),
            'robots' => 'index,follow',
            'type' => $type,
            'image' => $image,
            'published_at' => null,
            'modified_at' => null,
            'author' => null,
            'schema' => null,
        ];
    }

    /** @param array<string, mixed> $fallback
     * @return array<string, mixed>
     */
    private function productMetadata(Request $request, string $routeName, array $fallback): array
    {
        $segment = (string) $request->route('slug');
        $productSlug = match ($routeName) {
            'shop.sticker.show' => StickerProductCatalog::productSlugForSegment($segment) ?? $segment,
            'shop.postcard.show' => PostcardProductCatalog::productSlugForSegment($segment) ?? $segment,
            'shop.flyer.show' => FlyersAndBrochuresProductCatalog::productSlugForSegment($segment) ?? $segment,
            default => BusinessCardRoutes::productSlugForSegment($segment) ?? $segment,
        };

        $product = Product::query()
            ->where('is_active', true)
            ->where('slug', $productSlug)
            ->first();

        if (! $product) {
            return $fallback;
        }

        $canonicalPath = BusinessCardRoutes::pathForProductSlug($productSlug)
            ?? PostcardProductCatalog::pathForProductSlug($productSlug)
            ?? StickerProductCatalog::pathForProductSlug($productSlug)
            ?? FlyersAndBrochuresProductCatalog::pathForProductSlug($productSlug)
            ?? $request->getPathInfo();

        $description = $product->meta_description
            ?: ($product->description ?: 'Explore '.$product->name.' from InkPavo.');

        return [
            'title' => (string) $product->name,
            'description' => $this->description((string) $description),
            'robots' => 'index,follow',
            'type' => 'product',
            'image' => $this->imageUrl($product->featured_image, rtrim((string) config('app.url'), '/')),
            'canonical_path' => $canonicalPath,
            'published_at' => null,
            'modified_at' => null,
            'author' => null,
            'schema' => array_filter([
                '@type' => 'Product',
                'name' => $product->name,
                'description' => $this->description((string) $description),
                'image' => $this->imageUrl($product->featured_image, rtrim((string) config('app.url'), '/')),
                'brand' => ['@type' => 'Brand', 'name' => (string) config('app.name', 'InkPavo')],
                'url' => rtrim((string) config('app.url'), '/').$canonicalPath,
            ], static fn (mixed $value): bool => $value !== null),
        ];
    }

    private function description(string $value): string
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if (mb_strlen($text) > 160) {
            $text = rtrim(mb_substr($text, 0, 157)).'...';
        }

        return $text;
    }

    private function imageUrl(?string $image, string $baseUrl): ?string
    {
        if (! is_string($image) || trim($image) === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $image) === 1) {
            return $image;
        }

        return $baseUrl.'/'.ltrim($image, '/');
    }

    private function fullTitle(string $title, string $appName): string
    {
        $title = trim($title);

        if ($title === '' || str_ends_with(mb_strtolower($title), mb_strtolower($appName))) {
            return $title === '' ? $appName : $title;
        }

        return $title.' - '.$appName;
    }
}
