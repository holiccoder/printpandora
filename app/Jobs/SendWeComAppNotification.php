<?php

namespace App\Jobs;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\WeComSupportBridge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWeComAppNotification implements ShouldQueue
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

    public function handle(WeComSupportBridge $bridge): void
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
