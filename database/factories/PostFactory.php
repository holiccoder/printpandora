<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(rand(4, 8));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => $this->generateBody(),
            'featured_image' => null,
            'category_id' => null,
            'admin_id' => null,
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    protected function generateBody(): string
    {
        $paragraphs = fake()->paragraphs(rand(3, 6));

        $body = '';
        foreach ($paragraphs as $i => $p) {
            $body .= "<p>{$p}</p>";
            if ($i === 0) {
                $body .= '<h2>'.fake()->sentence(rand(4, 7)).'</h2>';
            }
        }

        return $body;
    }
}
