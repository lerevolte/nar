<?php

namespace App\Jobs;

use App\Services\BroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Переопределяет дефолтный --timeout=60 воркера. */
    public int $timeout = 3600;

    /** При таймауте/падении джоба ретраится и продолжает с last_user_id. */
    public int $tries = 30;

    public int $backoff = 5;

    public function __construct(public int $broadcastId) {}

    public function handle(BroadcastService $service): void
    {
        $service->runBroadcast($this->broadcastId);
    }
}
