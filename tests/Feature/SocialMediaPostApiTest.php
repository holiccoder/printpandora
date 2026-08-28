<?php

namespace Tests\Feature;

use App\Models\SocialMediaPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMediaPostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_social_media_posts(): void
    {
        SocialMediaPost::create([
            'title' => 'Test Post',
            'content' => 'Hello social media!',
            'platforms' => ['facebook', 'x'],
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/social-media-posts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test Post');
    }

    public function test_can_create_draft_social_media_post(): void
    {
        $response = $this->postJson('/api/v1/social-media-posts', [
            'title' => 'Scheduled Facebook Post',
            'content' => 'This is a scheduled post.',
            'platforms' => ['facebook', 'instagram'],
            'scheduled_at' => now()->addDays(2)->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Scheduled Facebook Post')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('social_media_posts', [
            'title' => 'Scheduled Facebook Post',
            'status' => 'draft',
        ]);
    }

    public function test_can_create_platform_specific_content(): void
    {
        $response = $this->postJson('/api/v1/social-media-posts', [
            'content' => 'Default post copy',
            'platforms' => ['facebook', 'x'],
            'platform_contents' => [
                'facebook' => 'Facebook-specific copy',
                'x' => 'Short copy for X',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.platform_contents.facebook', 'Facebook-specific copy')
            ->assertJsonPath('data.platform_contents.x', 'Short copy for X');
    }
}
