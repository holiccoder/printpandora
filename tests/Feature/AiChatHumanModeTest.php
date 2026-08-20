<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiChatHumanModeTest extends TestCase
{
    use RefreshDatabase;

    private function sessionId(): string
    {
        return (string) Str::uuid();
    }

    public function test_handoff_switches_the_conversation_to_human_mode(): void
    {
        $sessionId = $this->sessionId();

        $this->postJson('/ai/chat/handoff', ['session_id' => $sessionId])
            ->assertOk()
            ->assertJson(['mode' => 'human']);

        $this->assertDatabaseHas('ai_chat_conversations', [
            'session_id' => $sessionId,
            'mode' => 'human',
        ]);
    }

    public function test_ai_endpoint_rejects_messages_in_human_mode(): void
    {
        $sessionId = $this->sessionId();
        $this->postJson('/ai/chat/handoff', ['session_id' => $sessionId]);

        $this->postJson('/ai/chat', [
            'message' => 'are you there?',
            'session_id' => $sessionId,
        ])->assertConflict();
    }

    public function test_customer_can_post_a_message_with_an_attachment(): void
    {
        Storage::fake('public');

        $sessionId = $this->sessionId();

        $response = $this->postJson('/ai/chat/message', [
            'session_id' => $sessionId,
            'message' => 'Here is my design draft.',
            'attachment' => UploadedFile::fake()->image('draft.png'),
        ]);

        $response->assertCreated();

        $message = $response->json('message');
        $this->assertSame('Here is my design draft.', $message['content']);
        $this->assertSame('draft.png', $message['attachment_name']);
        $this->assertNotEmpty($message['attachment_url']);

        Storage::disk('public')->assertExists(
            \App\Models\AiChatMessage::query()->latest('id')->first()->attachment_path,
        );
    }

    public function test_message_endpoint_requires_text_or_attachment(): void
    {
        $this->postJson('/ai/chat/message', [
            'session_id' => $this->sessionId(),
        ])->assertUnprocessable();
    }

    public function test_poll_returns_new_messages_and_mode(): void
    {
        $sessionId = $this->sessionId();
        $this->postJson('/ai/chat/handoff', ['session_id' => $sessionId]);

        $conversation = AiChatConversation::query()->where('session_id', $sessionId)->firstOrFail();
        $first = $conversation->messages()->create(['role' => 'user', 'content' => 'hi']);
        $conversation->messages()->create(['role' => 'admin', 'content' => 'hello, how can I help?']);

        $response = $this->getJson("/ai/chat/poll?session_id={$sessionId}&after_id={$first->id}");

        $response->assertOk()->assertJson(['mode' => 'human']);
        $this->assertCount(1, $response->json('messages'));
        $this->assertSame('hello, how can I help?', $response->json('messages.0.content'));
    }

    public function test_admin_endpoints_require_the_admin_guard(): void
    {
        // Guests are redirected to the Filament admin login.
        $this->getJson('/ai/chat/admin/conversations')->assertRedirect();
    }

    public function test_admin_can_list_and_reply_to_human_conversations(): void
    {
        $admin = Admin::factory()->create();
        $customer = User::factory()->create();

        $sessionId = $this->sessionId();
        $this->actingAs($customer)->postJson('/ai/chat/handoff', ['session_id' => $sessionId]);
        $conversation = AiChatConversation::query()->where('session_id', $sessionId)->firstOrFail();
        $conversation->messages()->create(['role' => 'user', 'content' => 'I need help']);

        $list = $this->actingAs($admin, 'admin')
            ->getJson('/ai/chat/admin/conversations')
            ->assertOk();

        $this->assertSame(1, $list->json('waiting_count'));
        $this->assertSame($customer->email, $list->json('conversations.0.customer'));

        $this->actingAs($admin, 'admin')
            ->postJson("/ai/chat/admin/conversations/{$conversation->id}/reply", [
                'message' => 'Sure, what do you need?',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Sure, what do you need?',
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson("/ai/chat/admin/conversations/{$conversation->id}/resolve")
            ->assertOk()
            ->assertJson(['mode' => 'ai']);

        $this->assertSame('ai', $conversation->refresh()->mode);
    }

    public function test_admin_can_take_over_an_ai_conversation(): void
    {
        $admin = Admin::factory()->create();
        $conversation = AiChatConversation::create(['session_id' => $this->sessionId()]);

        $this->actingAs($admin, 'admin')
            ->postJson("/ai/chat/admin/conversations/{$conversation->id}/takeover")
            ->assertOk()
            ->assertJson(['mode' => 'human']);

        $this->assertSame('human', $conversation->refresh()->mode);
    }
}
