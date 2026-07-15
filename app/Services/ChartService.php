<?php

namespace App\Services;

use App\Models\Chart;
use App\Models\ChartEntry;
use App\Models\ChartReward;
use App\Models\ChartVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChartService
{
    /**
     * Минимальный возраст аккаунта (в днях), с которого разрешено голосовать.
     */
    public const MIN_ACCOUNT_AGE_DAYS = 10;

    /**
     * Максимум голосов с одного IP-адреса за сутки (по всем аккаунтам).
     */
    public const IP_DAILY_LIMIT = 15;

    /**
     * Призы по местам (песни на баланс)
     */
    public const REWARDS = [
        1 => 10, // 1 место — 10 песен
        2 => 7,  // 2 место — 7 песен
        3 => 5,  // 3 место — 5 песен
        4 => 3,  // 4 место — 3 песни
        5 => 1,  // 5 место — 1 песня
    ];

    /**
     * Призы для тематических чартов
     */
    public const THEME_REWARDS = [
        'valentine' => [
            1 => 10, // 1 место — 10 песен
            2 => 6,  // 2 место — 6 песен
            3 => 4,  // 3 место — 4 песни
        ],
        // Можно добавить другие темы
    ];

    /**
     * Минимум голосов для получения приза
     */
    public const MIN_VOTES_FOR_REWARD = 1;

    protected TelegramNotificationService $telegram;

    public function __construct(TelegramNotificationService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Получить призы для чарта
     */
    public static function getRewardsForChart(?Chart $chart): array
    {
        if ($chart && $chart->theme && isset(self::THEME_REWARDS[$chart->theme])) {
            return self::THEME_REWARDS[$chart->theme];
        }

        return self::REWARDS;
    }

    /**
     * Витрина «Лучшие песни» для главной и /create-song: случайные призёры
     * чартов (1–5 места, по chart_rewards) с обложками, не более одной песни
     * на автора.
     */
    public function getShowcaseTracks(int $limit = 20): array
    {
        $rewards = ChartReward::with(['entry.song', 'user'])
            ->whereBetween('position', [1, 5])
            ->whereHas('entry.song', function ($q) {
                $q->whereNotNull('file_path')
                    ->whereNotNull('cover_url')
                    ->where('cover_url', '!=', '');
            })
            ->inRandomOrder()
            ->get()
            ->unique(fn ($r) => $r->entry?->song_id) // одна песня могла призоваться в нескольких чартах
            ->unique('user_id') // разные авторы
            // разные пользователи с одинаковым именем выглядят как один автор — тоже дедуплицируем
            ->unique(fn ($r) => mb_strtolower(trim($r->user->first_name ?? $r->user->username ?? 'Автор')))
            ->take($limit)
            ->values();

        return $rewards->map(function ($reward, $index) {
            $entry = $reward->entry;
            $song = $entry?->song;
            if (! $song || ! $song->file_path) {
                return null;
            }

            return [
                'position' => $index + 1,
                'song_id' => $entry->song_id,
                'title' => $song->title ?? 'Без названия',
                'author' => $reward->user->first_name ?? $reward->user->username ?? 'Автор',
                'votes' => (int) $entry->votes_count,
                'plays' => $song->plays_count ?? 0,
                // старый домен narepite.site отдаёт 301 — переписываем на основной
                'audio_url' => str_replace('://narepite.site/', '://narepite.com/', (string) $song->file_path),
                'cover_url' => str_replace('://narepite.site/', '://narepite.com/', (string) $song->cover_url),
                'genre' => $song->genre,
                'occasion' => $song->occasion,
                'lyrics' => $song->lyrics,
                'created_at' => $song->created_at ? $song->created_at->format('d.m.Y') : null,
                'user_id' => $reward->user_id,
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Получить или создать текущий недельный чарт
     */
    public function getOrCreateCurrentChart(): Chart
    {
        // Ищем активный недельный чарт
        $chart = Chart::where('is_active', true)
            ->where('period', 'weekly')
            ->first();

        if ($chart) {
            // Проверяем не истёк ли он
            if ($chart->isExpired()) {
                // Закрываем и выдаём призы
                $this->closeChart($chart);

                // Создаём новый
                return $this->createWeeklyChart();
            }

            return $chart;
        }

        return $this->createWeeklyChart();
    }

    /**
     * Создать или получить тематический чарт
     */
    public function getOrCreateThemeChart(string $theme): ?Chart
    {
        // Ищем активный тематический чарт
        $chart = Chart::where('theme', $theme)
            ->where('is_active', true)
            ->first();

        if ($chart) {
            if ($chart->isExpired()) {
                $this->closeChart($chart);

                return null; // Чарт завершён
            }

            return $chart;
        }

        return null;
    }

    /**
     * Создать чарт на 14 февраля
     */
    public function createValentineChart(): Chart
    {
        $year = now()->year;

        // Проверяем, не создан ли уже
        $existing = Chart::where('theme', 'valentine')
            ->where('slug', "valentine-{$year}")
            ->first();

        if ($existing) {
            return $existing;
        }

        return Chart::create([
            'name' => 'Песни о любви 💕',
            'slug' => "valentine-{$year}",
            'period' => 'theme',
            'theme' => 'valentine',
            'description' => 'Поделись своей историей любви в музыке! Лучшие песни получат призы 14 февраля.',
            'cover_emoji' => '💕',
            'is_active' => true,
            'starts_at' => now(),
            'ends_at' => Carbon::create($year, 2, 14, 23, 59, 59),
        ]);
    }

    /**
     * Создать недельный чарт
     */
    public function createWeeklyChart(): Chart
    {
        $now = Carbon::now();

        // Проверяем, есть ли уже завершённые чарты (это не первый чарт)
        $hasCompletedCharts = Chart::where('is_active', false)
            ->where('period', 'weekly')
            ->exists();

        if ($hasCompletedCharts) {
            // Обычная логика: понедельник — воскресенье текущей недели
            $startsAt = $now->copy()->startOfWeek();
            $endsAt = $now->copy()->endOfWeek()->endOfDay();
            $weekNumber = $now->weekOfYear;
        } else {
            // Первый чарт: начинается сейчас, заканчивается в СЛЕДУЮЩЕЕ воскресенье
            $startsAt = $now->copy();
            $endsAt = $now->copy()->endOfWeek()->addWeek()->endOfDay();

            // Если сегодня воскресенье — берём следующее воскресенье
            if ($now->isSunday()) {
                $endsAt = $now->copy()->addWeek()->endOfDay();
            }

            $weekNumber = $endsAt->weekOfYear;
        }

        $year = $endsAt->year;

        return Chart::create([
            'name' => "Чарт недели #{$weekNumber}",
            'slug' => "week-{$year}-{$weekNumber}",
            'period' => 'weekly',
            'is_active' => true,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * Закрыть чарт и выдать награды
     */
    public function closeChart(Chart $chart): array
    {
        if (! $chart->is_active) {
            return ['status' => 'already_closed'];
        }

        // Проверяем, не выданы ли уже награды
        if ($chart->hasRewards()) {
            $chart->update(['is_active' => false]);

            return ['status' => 'rewards_already_given'];
        }

        $results = [
            'status' => 'success',
            'chart_id' => $chart->id,
            'chart_name' => $chart->name,
            'rewards' => [],
            'winners' => [],
        ];

        DB::transaction(function () use ($chart, &$results) {
            // Получаем топ-3 с минимум 1 голосом
            $topEntries = $chart->entries()
                ->with(['user', 'song'])
                ->where('votes_count', '>=', 1)
                ->orderByDesc('votes_count')
                ->orderBy('created_at') // При равенстве - кто раньше добавил
                ->take(5)
                ->get();

            foreach ($topEntries as $index => $entry) {
                $position = $index + 1;
                $rewards = self::getRewardsForChart($chart);
                $reward = $rewards[$position] ?? 0;

                if ($reward > 0) {
                    // Создаём запись о награде
                    ChartReward::create([
                        'chart_id' => $chart->id,
                        'user_id' => $entry->user_id,
                        'chart_entry_id' => $entry->id,
                        'position' => $position,
                        'songs_reward' => $reward,
                    ]);

                    // Начисляем баланс
                    $entry->user->increment('balance', $reward);

                    $results['rewards'][] = [
                        'position' => $position,
                        'user_id' => $entry->user_id,
                        'song_title' => $entry->song->title,
                        'songs_reward' => $reward,
                    ];

                    $results['winners'][] = [
                        'song' => $entry->song->title,
                        'author' => $entry->user->first_name ?? $entry->user->username ?? 'Автор',
                        'votes' => $entry->votes_count,
                        'audio_url' => $entry->song->file_path,
                    ];

                    // Отправляем уведомление победителю
                    $this->telegram->notifyChartWinner(
                        $entry->user_id,
                        $position,
                        $entry->song->title,
                        $reward,
                        $chart->name
                    );

                    Log::info("Chart reward: position {$position}, user {$entry->user_id}, +{$reward} songs");
                }
            }

            // Закрываем чарт
            $chart->update(['is_active' => false]);
        });

        // Отправляем результаты только неактивным пользователям
        if (! empty($results['winners'])) {
            $this->notifyInactiveUsers($chart, $results['winners'], $results['rewards']);
        }

        return $results;
    }

    /**
     * Отправить результаты чарта только неактивным пользователям
     *
     * Критерии неактивности (любой из):
     * 1. Есть только черновик, обновлённый > 7 дней назад (нет готовых песен)
     * 2. Последняя активность > 7 дней назад
     * 3. Последняя оплата > 10 дней назад (для тех, кто платил)
     */
    protected function notifyInactiveUsers(Chart $chart, array $winners, array $rewards): void
    {
        $winnerIds = collect($rewards)->pluck('user_id')->toArray();

        // Собираем ID неактивных пользователей
        $inactiveUserIds = $this->getInactiveUserIds($winnerIds);

        if (empty($inactiveUserIds)) {
            Log::info("Chart {$chart->id}: No inactive users to notify");

            return;
        }

        Log::info("Chart {$chart->id}: Notifying ".count($inactiveUserIds).' inactive users');

        $this->telegram->notifyChartResults(
            $inactiveUserIds,
            $chart->name,
            $winners
        );
    }

    /**
     * Получить ID неактивных пользователей
     */
    protected function getInactiveUserIds(array $excludeUserIds = []): array
    {
        $inactiveIds = collect();

        // 1. Пользователи с черновиком > 7 дней назад и без готовых песен
        $draftOnlyUsers = DB::table('drafts as d')
            ->leftJoin('songs as s', 'd.user_id', '=', 's.user_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->whereNull('s.id') // нет готовых песен
            ->where('u.is_blocked', 0)
            ->where('d.updated_at', '<', now()->subDays(7))
            ->whereNotIn('d.user_id', $excludeUserIds)
            ->pluck('d.user_id');

        $inactiveIds = $inactiveIds->merge($draftOnlyUsers);

        // 2. Пользователи с последней активностью > 7 дней назад
        $inactiveUsers = DB::table('users')
            ->where('is_blocked', 0)
            ->where('last_activity', '<', now()->subDays(7))
            ->whereNotIn('user_id', $excludeUserIds)
            ->pluck('user_id');

        $inactiveIds = $inactiveIds->merge($inactiveUsers);

        // 3. Пользователи с последней оплатой > 10 дней назад
        $churnUsers = DB::table('users as u')
            ->join('payments as p', 'u.user_id', '=', 'p.user_id')
            ->where('u.is_blocked', 0)
            ->where('p.status', 'succeeded')
            ->whereNotIn('u.user_id', $excludeUserIds)
            ->groupBy('u.user_id')
            ->havingRaw('MAX(p.created_at) < ?', [now()->subDays(10)])
            ->pluck('u.user_id');

        $inactiveIds = $inactiveIds->merge($churnUsers);

        // Убираем дубликаты и возвращаем
        return $inactiveIds->unique()->values()->toArray();
    }

    /**
     * Закрыть все просроченные чарты
     */
    public function closeExpiredCharts(): array
    {
        $expiredCharts = Chart::where('is_active', true)
            ->where('ends_at', '<', now())
            ->get();

        $results = [];

        foreach ($expiredCharts as $chart) {
            $results[] = $this->closeChart($chart);
        }

        return $results;
    }

    /**
     * Проверить, может ли пользователь добавить песню в чарт
     */
    public function canUserAddSong(int $userId, int $chartId): array
    {
        // Проверяем лимит — 1 песня на чарт
        $existingEntry = ChartEntry::where('chart_id', $chartId)
            ->where('user_id', $userId)
            ->first();

        if ($existingEntry) {
            return [
                'can_add' => false,
                'reason' => 'Вы уже добавили песню в этот чарт',
                'existing_entry' => $existingEntry,
            ];
        }

        return ['can_add' => true];
    }

    /**
     * Проверить, участвовала ли песня уже в другом чарте.
     * Одну песню можно добавить только в один чарт.
     */
    public function songUsedInAnotherChart(int $songId, int $chartId): bool
    {
        return ChartEntry::where('song_id', $songId)
            ->where('chart_id', '!=', $chartId)
            ->exists();
    }

    /**
     * Проверка защиты голосования от накрутки.
     * Возвращает текст ошибки, если голосовать нельзя, либо null — если можно.
     *
     * Правила:
     * 1. Аккаунт должен быть старше MIN_ACCOUNT_AGE_DAYS дней.
     * 2. За эту песню ещё не голосовали с этого же устройства (другим аккаунтом).
     * 3. За эту песню ещё не голосовали с этого же IP (другим аккаунтом).
     * 4. С этого IP за сутки сделано не больше IP_DAILY_LIMIT голосов (по всем аккаунтам).
     *
     * Проверки «за свою песню» и «уже голосовал / лимит 10 в день на аккаунт»
     * выполняются в контроллере отдельно.
     */
    public function voteRejectionReason(User $user, ?string $ip, ?string $deviceId, int $songId): ?string
    {
        // 1. Возраст аккаунта
        if ($user->created_at && $user->created_at->gt(now()->subDays(self::MIN_ACCOUNT_AGE_DAYS))) {
            return 'Голосовать могут аккаунты, зарегистрированные более '.self::MIN_ACCOUNT_AGE_DAYS.' дней назад';
        }

        $entryIds = ChartEntry::where('song_id', $songId)->pluck('id');

        // 2. Устройство: за эту песню уже голосовали с этого же устройства другим аккаунтом
        if ($deviceId) {
            $dupDevice = ChartVote::whereIn('chart_entry_id', $entryIds)
                ->where('device_id', $deviceId)
                ->where('user_id', '!=', $user->user_id)
                ->exists();

            if ($dupDevice) {
                return 'С этого устройства уже голосовали за эту песню';
            }
        }

        if ($ip) {
            // 3. IP: за эту песню уже голосовали с этого же IP другим аккаунтом
            $dupIp = ChartVote::whereIn('chart_entry_id', $entryIds)
                ->where('ip_address', $ip)
                ->where('user_id', '!=', $user->user_id)
                ->exists();

            if ($dupIp) {
                return 'С этого IP-адреса уже голосовали за эту песню';
            }

            // 4. Суточный лимит голосов с одного IP (по всем аккаунтам)
            $ipVotesToday = ChartVote::where('ip_address', $ip)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($ipVotesToday >= self::IP_DAILY_LIMIT) {
                return 'Слишком много голосов с этого IP-адреса за сегодня';
            }
        }

        return null;
    }
}
