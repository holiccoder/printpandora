<?php

namespace Tests\Feature;

use App\Ai\Agents\CustomerSupportAgent;
use App\Models\AiChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return [
            'message' => 'How long does shipping take?',
            'session_id' => (string) Str::uuid(),
            'history' => [],
            ...$overrides,
        ];
    }

    public function test_it_requires_a_message_and_a_uuid_session_id(): void
    {
        $this->postJson('/ai/chat', [])->assertUnprocessable();

        $this->postJson('/ai/chat', $this->validPayload([
            'session_id' => 'not-a-uuid',
        ]))->assertUnprocessable();

        $this->postJson('/ai/chat', $this->validPayload([
            'message' => str_repeat('a', 1001),
        ]))->assertUnprocessable();
    }

    public function test_it_404s_when_the_chat_is_disabled(): void
    {
        config()->set('aichat.enabled', false);

        $this->postJson('/ai/chat', $this->validPayload())->assertNotFound();
    }

    public function test_it_streams_a_reply_and_persists_both_messages(): void
    {
        Embeddings::fake([[[1.0, 0.0, 0.0]]]);
        CustomerSupportAgent::fake(['Standard shipping takes 7 days.']);

        $sessionId = (string) Str::uuid();

        $response = $this->post('/ai/chat', $this->validPayload([
            'session_id' => $sessionId,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/event-stream; charset=UTF-8');

        // Consuming the stream triggers the completion callback that
        // persists the assistant reply.
        $content = $response->streamedContent();

        $this->assertStringContainsString('text_delta', $content);
        $this->assertStringContainsString('Standard', $content);

        $conversation = AiChatConversation::query()
            ->where('session_id', $sessionId)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertNull($conversation->user_id);

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'How long does shipping take?',
        ]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Standard shipping takes 7 days.',
        ]);

        CustomerSupportAgent::assertPrompted('How long does shipping take?');
    }

    public function test_it_links_the_conversation_to_the_authenticated_user(): void
    {
        Embeddings::fake([[[1.0, 0.0, 0.0]]]);
        CustomerSupportAgent::fake(['Sure!']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/ai/chat', $this->validPayload());
        $response->streamedContent();

        $this->assertDatabaseHas('ai_chat_conversations', [
            'user_id' => $user->id,
        ]);
    }
}
