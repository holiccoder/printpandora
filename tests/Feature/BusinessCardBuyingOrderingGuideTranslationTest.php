<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardBuyingOrderingGuideTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_card_buying_ordering_guide_is_published_in_english(): void
    {
        $post = Post::query()
            ->where('slug', 'business-card-buying-ordering-guide')
            ->firstOrFail();

        $this->assertSame(
            'Business Card Buying & Ordering Guide: Materials, Sizes, Finishes, and Lead Times',
            $post->title,
        );
        $this->assertSame(
            '/images/blog/business-card-buying-ordering-guide-featured.webp',
            $post->featured_image,
        );
        $this->assertStringContainsString(
            '<h2>Three questions to answer before buying business cards</h2>',
            $post->body,
        );
        $this->assertStringContainsString(
            '<a href="/business-cards">business card product page</a>',
            $post->body,
        );
        $this->assertStringNotContainsString('名片', $post->title . $post->body);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('blog/show')
                ->where('post.slug', $post->slug)
                ->where('post.title', $post->title));
    }
}
