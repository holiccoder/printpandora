<?php

namespace App\Jobs;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\FeishuSupportBridge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendFeishuNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public AiChatConversation $conversation,
        public AiChatMessage $message,
    ) {}

    public function handle(FeishuSupportBridge $bridge): void
    {
        $bridge->notifyCustomerMessage($this->conversation, $this->message);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }
}
