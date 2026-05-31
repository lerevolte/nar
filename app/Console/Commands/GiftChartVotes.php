<?php

namespace App\Console\Commands;

use App\Models\Chart;
use App\Models\ChartEntry;
use App\Models\ChartVote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftChartVotes extends Command
{
    protected $signature = 'charts:gift-votes
        {--count=6 : Количество песен, которым раздать голоса}
        {--votes=1 : Сколько голосов каждой песне}
        {--exclude=* : ID песен для исключения (song_id)}';

    protected $description = 'Раздать голоса случайным песням в текущем еженедельном чарте';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $votesPerSong = (int) $this->option('votes');
        $exclude = array_filter(array_map('intval', $this->option('exclude')));

        // Находим активный недельный чарт
        $chart = Chart::where('is_active', true)
            ->where('period', 'weekly')
            ->first();

        if (!$chart) {
            $this->error('Нет активного еженедельного чарта.');
            return 1;
        }

        $this->info("Чарт: {$chart->name} (ID: {$chart->id})");

        // Берём записи чарта, исключая указанные песни
        $query = ChartEntry::where('chart_id', $chart->id);

        if (!empty($exclude)) {
            $query->whereNotIn('song_id', $exclude);
            $this->info("Исключены песни: " . implode(', ', $exclude));
        }

        $entries = $query->with(['song', 'user'])->inRandomOrder()->limit($count)->get();

        if ($entries->isEmpty()) {
            $this->info('Нет подходящих песен в чарте.');
            return 0;
        }

        $this->info("Раздаю по {$votesPerSong} голосов {$entries->count()} песням:");

        $gifted = 0;

        foreach ($entries as $entry) {
            $voted = 0;

            DB::transaction(function () use ($entry, $votesPerSong, &$voted) {
                $systemUserIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

                foreach ($systemUserIds as $sysId) {
                    if ($voted >= $votesPerSong) break;

                    // Пропускаем если уже голосовал
                    $exists = ChartVote::where('chart_entry_id', $entry->id)
                        ->where('user_id', $sysId)
                        ->exists();

                    if ($exists) continue;

                    // Нельзя голосовать за свою песню
                    if ($entry->user_id === $sysId) continue;

                    ChartVote::create([
                        'chart_entry_id' => $entry->id,
                        'user_id' => $sysId,
                    ]);
                    $voted++;
                }

                if ($voted > 0) {
                    $entry->increment('votes_count', $voted);
                }
            });

            $songTitle = $entry->song->title ?? 'Без названия';
            $author = $entry->user->first_name ?? $entry->user->username ?? 'Автор';
            $newVotes = $entry->fresh()->votes_count;

            if ($voted > 0) {
                $this->line("  ✅ «{$songTitle}» ({$author}) — +{$voted} голос → итого {$newVotes}");
                Log::info("Gift vote: +{$voted} to entry {$entry->id} (song: {$entry->song_id}), chart {$entry->chart_id}");
                $gifted++;
            } else {
                $this->line("  ⏭ «{$songTitle}» — все системные уже голосовали");
            }
        }

        $total = $gifted * $votesPerSong;
        $this->info("Готово! Роздано: {$gifted} песен × {$votesPerSong} = {$total} голосов.");

        return 0;
    }
}