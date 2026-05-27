<?php

namespace App\Console\Commands;

use App\Services\ChartService;
use Illuminate\Console\Command;

class CloseExpiredCharts extends Command
{
    protected $signature = 'charts:close-expired';
    protected $description = 'Close expired charts and award prizes';

    public function handle(ChartService $chartService)
    {
        $this->info('Checking for expired charts...');

        $results = $chartService->closeExpiredCharts();

        if (empty($results)) {
            $this->info('No expired charts found.');
            return 0;
        }

        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                $this->info("✓ Closed chart: {$result['chart_name']}");
                
                foreach ($result['rewards'] as $reward) {
                    $this->line("  - Position {$reward['position']}: {$reward['song_title']} (+{$reward['songs_reward']} songs)");
                }
                
                $this->info("  Notifications sent to winners and participants");
            } else {
                $this->warn("Chart {$result['chart_id']}: {$result['status']}");
            }
        }

        $this->info('Done!');
        return 0;
    }
}