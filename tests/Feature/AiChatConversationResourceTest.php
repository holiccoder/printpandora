<?php

namespace Tests\Feature;

use App\Filament\Resources\AiChatConversationResource\Pages\ListAiChatConversations;
use App\Filament\Resources\AiChatConversationResource\Pages\ViewAiChatConversation;
use App\Models\Admin;
use App\Models\AiChatConversation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiChatConversationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_admin_can_list_ai_chat_conversations(): void
    {
        $conversation = AiChatConversation::create(['session_id' => 'test-session']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Hello there']);

        Livewire::test(ListAiChatConversations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$conversation]);
    }

    public function test_admin_can_view_a_conversation_with_its_messages(): void
    {
        $conversation = AiChatConversation::create(['session_id' => 'test-session']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'How long is shipping?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'About 7 days.']);

        Livewire::test(ViewAiChatConversation::class, ['record' => $conversation->id])
            ->assertOk()
            ->assertSee('How long is shipping?')
            ->assertSee('About 7 days.');
    }
}
