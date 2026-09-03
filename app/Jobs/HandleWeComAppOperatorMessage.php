<?php

namespace App\Jobs;

use App\Services\WeComSupportBridge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class HandleWeComAppOperatorMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $userId,
        public string $msgId,
        public string $text,
    ) {}

    public function handle(WeComSupportBridge $bridge): void
    {
        $bridge->handleOperatorMessage($this->userId, $this->msgId, $this->text);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }
}
