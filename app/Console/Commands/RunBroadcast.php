<?php

namespace App\Console\Commands;

use App\Services\BroadcastService;
use Illuminate\Console\Command;

class RunBroadcast extends Command
{
    protected $signature = 'broadcast:run {id}';
    protected $description = 'Run a pending broadcast by ID';

    public function handle(BroadcastService $service)
    {
        $id = (int) $this->argument('id');

        $this->info("Starting broadcast #{$id}...");

        $result = $service->runBroadcast($id, function ($sent, $failed, $blocked, $total) {
            $progress = $total > 0 ? round(($sent + $failed + $blocked) / $total * 100) : 0;
            $this->line("  Progress: {$progress}% — sent:{$sent} failed:{$failed} blocked:{$blocked}");
        });

        $this->newLine();
        $this->info("Broadcast #{$id} finished!");
        $this->line("  Status: {$result['status']}");
        $this->line("  ✅ Sent: {$result['sent']}");
        $this->line("  ❌ Failed: {$result['failed']}");
        $this->line("  🚫 Blocked: {$result['blocked']}");

        return 0;
    }
}