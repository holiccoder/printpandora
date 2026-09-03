<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_uses_the_latest_four_published_posts_for_the_carousel_and_nav(): void
    {
        $admin = Admin::factory()->create();
        $category = Category::query()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $createPost = function (int $daysAgo, string $suffix) use ($admin, $category): Post {
            return Post::query()->create([
                'title' => "Blog post {$suffix}",
                'slug' => "blog-post-{$suffix}",
                'body' => '<p>Blog post body.</p>',
                'featured_image' => null,
                'category_id' => $category->id,
                'admin_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDays($daysAgo),
            ]);
        };

        $latest = $createPost(0, 'latest');
        $createPost(1, 'second');
        $createPost(2, 'third');
        $fourth = $createPost(3, 'fourth');
        $createPost(10, 'older');

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('blog/index')
                ->has('carouselPosts', 4)
                ->where('carouselPosts.0.id', $latest->id)
                ->where('carouselPosts.3.id', $fourth->id)
                ->has('blog_dropdown_posts', 4)
                ->where('blog_dropdown_posts.0.slug', $latest->slug)
                ->where('blog_dropdown_posts.3.slug', $fourth->slug));
    }
}
