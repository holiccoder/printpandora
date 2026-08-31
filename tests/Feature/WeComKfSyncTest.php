<?php

namespace Tests\Feature;

use App\Jobs\GenerateWeComAiReply;
use App\Jobs\SyncWeComKfMessages;
use App\Models\AiChatConversation;
use App\Services\AiChatTranslationService;
use App\Services\WeComKfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeComKfSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.kf_secret', 'test-kf-secret');
        config()->set('services.wecom.open_kfid', 'wk-test');
        config()->set('services.wecom.timeout', 10);
        config()->set('aichat.translation.enabled', false);
        config()->set('aichat.wecom_handoff_keywords', ['人工', 'human', '转人工']);
        Cache::flush();
    }

    public function test_customer_messages_create_ai_conversations_and_are_mapped_idempotently(): void
    {
        Queue::fake();
        $this->fakeSync([
            [
                'msgid' => 'wecom-msg-1',
                'external_userid' => 'wm-customer-1',
                'origin' => 3,
                'msgtype' => 'text',
                'text' => ['content' => 'How long does shipping take?'],
            ],
        ]);
        $job = new SyncWeComKfMessages('pull-token', 'wk-test');

        $job->handle(new WeComKfService, new AiChatTranslationService);
        $job->handle(new WeComKfService, new AiChatTranslationService);

        $conversation = AiChatConversation::query()->firstOrFail();

        $this->assertSame('ai', $conversation->mode);
        $this->assertSame('wm-customer-1', $conversation->channels()->value('external_chat_id'));
        $this->assertSame(1, $conversation->messages()->count());
        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'How long does shipping take?',
        ]);
        $this->assertDatabaseCount('ai_chat_wecom_messages', 1);
        $this->assertSame('cursor-1', Cache::get('wecom:kf:cursor'));
        Queue::assertPushed(GenerateWeComAiReply::class, 1);
    }

    public function test_human_conversations_store_wecom_messages_without_dispatching_ai(): void
    {
        Queue::fake();
        $conversation = AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'human',
        ]);
        $conversation->channels()->create([
            'provider' => WeComKfService::PROVIDER,
            'external_chat_id' => 'wm-human',
        ]);
        $this->fakeSync([
            [
                'msgid' => 'wecom-human-msg',
                'external_userid' => 'wm-human',
                'origin' => 3,
                'msgtype' => 'text',
                'text' => ['content' => 'I need a person.'],
            ],
        ]);

        (new SyncWeComKfMessages)->handle(new WeComKfService, new AiChatTranslationService);

        $this->assertSame(1, $conversation->messages()->count());
        Queue::assertNothingPushed();
    }

    public function test_handoff_keywords_switch_to_human_and_send_a_notice(): void
    {
        Queue::fake();
        $this->fakeSync([
            [
                'msgid' => 'wecom-handoff-msg',
                'external_userid' => 'wm-handoff',
                'origin' => 3,
                'msgtype' => 'text',
                'text' => ['content' => '请转人工客服'],
            ],
        ], true);

        (new SyncWeComKfMessages)->handle(new WeComKfService, new AiChatTranslationService);

        $conversation = AiChatConversation::query()->firstOrFail();

        $this->assertSame('human', $conversation->mode);
        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => config('aichat.wecom_handoff_message'),
        ]);
        Queue::assertNothingPushed();
        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with($request->url(), 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg')
                && $request->data()['touser'] === 'wm-handoff';
        });
    }

    /** @param array<int, array<string, mixed>> $messages */
    private function fakeSync(array $messages, bool $withSend = false): void
    {
        $fake = [
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'access_token' => 'test-access-token',
                'expires_in' => 7200,
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/kf/sync_msg*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'next_cursor' => 'cursor-1',
                'has_more' => 0,
                'msg_list' => $messages,
            ]),
        ];

        if ($withSend) {
            $fake['https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg*'] = Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'msgid' => 'wecom-outbound-1',
            ]);
        }

        Http::fake($fake);
    }
}
