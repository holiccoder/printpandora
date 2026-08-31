<?php

namespace Tests\Feature;

use App\Ai\Agents\CustomerSupportAgent;
use App\Jobs\GenerateWeComAiReply;
use App\Models\AiChatConversation;
use App\Services\AiKnowledgeRetriever;
use App\Services\WeComKfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;
use RuntimeException;
use Tests\TestCase;

class GenerateWeComAiReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.kf_secret', 'test-kf-secret');
        config()->set('services.wecom.open_kfid', 'wk-test');
        config()->set('aichat.wecom_history_limit', 10);
        Cache::flush();
    }

    public function test_it_generates_sends_and_persists_a_wecom_ai_reply(): void
    {
        $conversation = $this->conversation();
        $conversation->messages()->create([
            'role' => 'user',
            'content' => 'What paper do you recommend?',
        ]);
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'How long does shipping take?',
        ]);

        Embeddings::fake([[[1.0, 0.0, 0.0]]]);
        CustomerSupportAgent::fake(['Standard shipping takes 7 days.']);
        $this->fakeWeComApi();

        (new GenerateWeComAiReply($conversation, $message))
            ->handle(new AiKnowledgeRetriever, new WeComKfService);

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Standard shipping takes 7 days.',
        ]);
        CustomerSupportAgent::assertPrompted('How long does shipping take?');
        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with($request->url(), 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg')
                && $request->data()['touser'] === 'wm-ai'
                && $request->data()['text']['content'] === 'Standard shipping takes 7 days.';
        });
    }

    public function test_ai_failures_switch_to_human_and_send_a_fallback(): void
    {
        $conversation = $this->conversation();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Please answer this.',
        ]);

        Embeddings::fake([[[1.0, 0.0, 0.0]]]);
        CustomerSupportAgent::fake(fn () => throw new RuntimeException('AI provider is unavailable.'));
        $this->fakeWeComApi();

        (new GenerateWeComAiReply($conversation, $message))
            ->handle(new AiKnowledgeRetriever, new WeComKfService);

        $this->assertSame('human', $conversation->refresh()->mode);
        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => config('aichat.wecom_fallback_message'),
        ]);
        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with($request->url(), 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg')
                && $request->data()['text']['content'] === config('aichat.wecom_fallback_message');
        });
    }

    private function conversation(): AiChatConversation
    {
        $conversation = AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'ai',
        ]);
        $conversation->channels()->create([
            'provider' => WeComKfService::PROVIDER,
            'external_chat_id' => 'wm-ai',
        ]);

        return $conversation;
    }

    private function fakeWeComApi(): void
    {
        Http::fake([
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'access_token' => 'test-access-token',
                'expires_in' => 7200,
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'msgid' => 'wecom-outbound-1',
            ]),
        ]);
    }
}
