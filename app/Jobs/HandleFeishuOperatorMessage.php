<?php

namespace App\Jobs;

use App\Services\FeishuSupportBridge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class HandleFeishuOperatorMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $openId,
        public string $messageId,
        public string $text,
    ) {}

    public function handle(FeishuSupportBridge $bridge): void
    {
        $bridge->handleOperatorMessage($this->openId, $this->messageId, $this->text);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }
}
