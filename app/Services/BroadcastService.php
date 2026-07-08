<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\WebNotification;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastService
{
    /** Смещение user_id для пользователей MAX (см. narbot handlers_max). */
    public const MAX_USER_ID_OFFSET = 10000000000;

    /** Порог «неактивности» (дней) для спящих. */
    public const SLEEP_DAYS = 14;

    /** Порог оттока (дней с последней активности) для оплативших. */
    public const CHURN_DAYS = 14;

    protected TelegramNotificationService $telegram;

    protected MaxNotificationService $max;

    public function __construct(TelegramNotificationService $telegram, MaxNotificationService $max)
    {
        $this->telegram = $telegram;
        $this->max = $max;
    }

    /**
     * Метаданные сегментов для UI и валидации.
     * stuck=true — «зависшие», показываются карточками на дашборде.
     */
    public static function segments(): array
    {
        return [
            'no_create' => [
                'emoji' => '🫥', 'label' => 'Подписался — ничего не создал', 'stuck' => true,
                'desc' => 'Есть в базе, но нет ни черновика, ни песни.',
                'action' => 'Онбординг: покажи, как создать первую песню за 1 минуту. Дай ссылку/кнопку «Создать песню».',
            ],
            'draft' => [
                'emoji' => '📝', 'label' => 'Черновик без готовой песни', 'stuck' => true,
                'desc' => 'Начал создавать (есть draft), но не довёл до генерации.',
                'action' => 'Напоминание «допиши свою песню» + мягкий стимул закончить.',
            ],
            'no_pay' => [
                'emoji' => '🎧', 'label' => 'Есть песня, но не оплатил', 'stuck' => true,
                'desc' => 'Сгенерировал текст/демо, но нет успешной оплаты.',
                'action' => 'Оффер со скидкой/промокодом, чтобы забрать полную версию.',
            ],
            'churn' => [
                'emoji' => '💔', 'label' => 'Оплачивал, но пропал (отток)', 'stuck' => true,
                'desc' => 'Была успешная оплата, но неактивен более '.self::CHURN_DAYS.' дней.',
                'action' => 'Реактивация: новинки, повод вернуться, бонус на следующую песню.',
            ],
            'sleep' => [
                'emoji' => '💤', 'label' => 'Спящие ('.self::SLEEP_DAYS.'+ дней)', 'stuck' => true,
                'desc' => 'Не заходили более '.self::SLEEP_DAYS.' дней (любой стадии).',
                'action' => 'Широкая реактивационная рассылка с новостями/акцией.',
            ],
            'blocked' => [
                'emoji' => '🚫', 'label' => 'Заблокировали бота', 'stuck' => true,
                'desc' => 'is_blocked = 1. Доставка в мессенджер невозможна.',
                'action' => 'Слать нельзя. Показано для контроля оттока.',
            ],
            'paid' => [
                'emoji' => '💰', 'label' => 'Все оплатившие', 'stuck' => false,
                'desc' => 'Хотя бы одна успешная оплата.',
                'action' => 'Апселл, новые форматы, реферальная программа.',
            ],
            'all' => [
                'emoji' => '🌍', 'label' => 'Все активные', 'stuck' => false,
                'desc' => 'Все незаблокированные пользователи.',
                'action' => 'Массовые анонсы.',
            ],
        ];
    }

    public static function isValidSegment(string $segment): bool
    {
        return array_key_exists($segment, self::segments()) || $segment === 'inactive_mix' || $segment === 'test';
    }

    /**
     * Базовый query пользователей по сегменту (таблица users).
     */
    public function getUsersBySegment(string $segment): Builder
    {
        switch ($segment) {
            case 'no_create':
                return DB::table('users')
                    ->where('is_blocked', 0)
                    ->whereNotExists(fn ($q) => $q->from('drafts')->whereColumn('drafts.user_id', 'users.user_id'))
                    ->whereNotExists(fn ($q) => $q->from('songs')->whereColumn('songs.user_id', 'users.user_id'));

            case 'draft':
                return DB::table('users')
                    ->where('is_blocked', 0)
                    ->whereExists(fn ($q) => $q->from('drafts')->whereColumn('drafts.user_id', 'users.user_id'))
                    ->whereNotExists(fn ($q) => $q->from('songs')->whereColumn('songs.user_id', 'users.user_id'));

            case 'no_pay':
                return DB::table('users')
                    ->where('is_blocked', 0)
                    ->whereExists(fn ($q) => $q->from('songs')->whereColumn('songs.user_id', 'users.user_id'))
                    ->whereNotExists(fn ($q) => $q->from('payments')
                        ->whereColumn('payments.user_id', 'users.user_id')
                        ->where('payments.status', 'succeeded'));

            case 'churn':
                return DB::table('users')
                    ->where('is_blocked', 0)
                    ->where('last_activity', '<', now()->subDays(self::CHURN_DAYS))
                    ->whereExists(fn ($q) => $q->from('payments')
                        ->whereColumn('payments.user_id', 'users.user_id')
                        ->where('payments.status', 'succeeded'));

            case 'sleep':
                return DB::table('users')
                    ->where('is_blocked', 0)
                    ->where('last_activity', '<', now()->subDays(self::SLEEP_DAYS));

            case 'paid':
                return DB::table('users')
                    ->where('is_blocked', 0)
                    ->whereExists(fn ($q) => $q->from('payments')
                        ->whereColumn('payments.user_id', 'users.user_id')
                        ->where('payments.status', 'succeeded'));

            case 'blocked':
                return DB::table('users')->where('is_blocked', 1);

            case 'test':
                return DB::table('users')->where('user_id', 288559694);

            case 'inactive_mix': // legacy, оставлено для истории
                $inactiveIds = DB::table('users')->where('is_blocked', 0)
                    ->where('last_activity', '<', now()->subDays(7))->pluck('user_id');
                $draftIds = DB::table('drafts as d')
                    ->leftJoin('songs as s', 'd.user_id', '=', 's.user_id')
                    ->join('users as u', 'd.user_id', '=', 'u.user_id')
                    ->whereNull('s.id')->where('u.is_blocked', 0)
                    ->where('d.updated_at', '<', now()->subDays(7))->pluck('d.user_id');
                $churnIds = DB::table('users as u')
                    ->join('payments as p', 'u.user_id', '=', 'p.user_id')
                    ->where('u.is_blocked', 0)->where('p.status', 'succeeded')
                    ->groupBy('u.user_id')
                    ->havingRaw('MAX(p.created_at) < ?', [now()->subDays(10)])->pluck('u.user_id');
                $ids = $inactiveIds->merge($draftIds)->merge($churnIds)->unique();

                return DB::table('users')->whereIn('user_id', $ids);

            case 'all':
            default:
                return DB::table('users')->where('is_blocked', 0);
        }
    }

    /**
     * Ограничить query пользователями, достижимыми выбранными каналами.
     * web достижим для всех → фильтр не накладывается.
     */
    public function applyChannelFilter(Builder $query, array $channels): Builder
    {
        if (in_array('web', $channels, true)) {
            return $query; // веб-уведомление получают все
        }

        $off = self::MAX_USER_ID_OFFSET;

        return $query->where(function ($w) use ($channels, $off) {
            $any = false;
            if (in_array('telegram', $channels, true)) {
                $w->orWhere('user_id', '<', $off);
                $any = true;
            }
            if (in_array('max', $channels, true)) {
                $w->orWhere(fn ($m) => $m->where('user_id', '>=', $off)->whereNotNull('chat_id'));
                $any = true;
            }
            if (! $any) {
                $w->whereRaw('1=0');
            }
        });
    }

    /**
     * Кол-во получателей сегмента (опционально с учётом каналов).
     */
    public function countBySegment(string $segment, ?array $channels = null): int
    {
        $q = $this->getUsersBySegment($segment);
        if ($channels !== null && $channels !== []) {
            $this->applyChannelFilter($q, $channels);
        }

        return $q->count();
    }

    /**
     * Разбивка сегмента по каналам — для карточек дашборда.
     */
    public function segmentBreakdown(string $segment): array
    {
        $off = self::MAX_USER_ID_OFFSET;
        $base = $this->getUsersBySegment($segment);

        $total = (clone $base)->count();
        $tg = (clone $base)->where('user_id', '<', $off)->count();
        $maxAll = (clone $base)->where('user_id', '>=', $off)->count();
        $maxReach = (clone $base)->where('user_id', '>=', $off)->whereNotNull('chat_id')->count();

        return ['total' => $total, 'tg' => $tg, 'max' => $maxAll, 'max_reachable' => $maxReach];
    }

    /**
     * Каналы рассылки как массив (с учётом legacy-значения 'both').
     */
    public function channelsOf(Broadcast $broadcast): array
    {
        $raw = $broadcast->channel ?: 'telegram';
        if ($raw === 'both') {
            return ['telegram', 'web'];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Создать рассылку (pending). Каналы сохраняются списком через запятую.
     */
    public function createBroadcast(array $data): Broadcast
    {
        $segment = $data['segment'] ?? 'all';
        $channels = $data['channels'] ?? ['telegram'];

        return Broadcast::create([
            'admin_id' => $data['admin_id'],
            'type' => 'text',
            'channel' => implode(',', $channels),
            'status' => 'pending',
            'segment' => $segment,
            'total_users' => $this->countBySegment($segment, $channels),
            'text_content' => $data['text_content'] ?? null,
            'web_title' => $data['web_title'] ?? null,
            'web_message' => $data['web_message'] ?? null,
        ]);
    }

    /**
     * Запустить рассылку (из Job или artisan). Идемпотентно возобновляется
     * с last_user_id — при рестарте воркера продолжит с места остановки.
     */
    public function runBroadcast(int $broadcastId, ?callable $onProgress = null): array
    {
        $broadcast = Broadcast::findOrFail($broadcastId);

        if ($broadcast->status === 'completed') {
            return ['status' => 'completed', 'sent' => $broadcast->sent_count, 'failed' => $broadcast->failed_count, 'blocked' => $broadcast->blocked_count];
        }

        $broadcast->update(['status' => 'running', 'started_at' => $broadcast->started_at ?? now()]);

        $channels = $this->channelsOf($broadcast);
        $segment = $broadcast->segment ?: 'all';

        $sent = (int) $broadcast->sent_count;
        $failed = (int) $broadcast->failed_count;
        $blocked = (int) $broadcast->blocked_count;
        $last = (int) ($broadcast->last_user_id ?: 0);
        $processed = 0;
        $batchSize = 200;

        while (true) {
            $rows = $this->applyChannelFilter($this->getUsersBySegment($segment), $channels)
                ->where('user_id', '>', $last)
                ->orderBy('user_id')
                ->limit($batchSize)
                ->get(['user_id', 'chat_id']);

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $result = $this->sendToUser($broadcast, $row, $channels);

                if ($result === 'sent') {
                    $sent++;
                } elseif ($result === 'blocked') {
                    $blocked++;
                    DB::table('users')->where('user_id', $row->user_id)->update(['is_blocked' => 1]);
                } else {
                    $failed++;
                }

                $last = (int) $row->user_id;
                $processed++;

                if ($processed % 20 === 0) {
                    $broadcast->update([
                        'sent_count' => $sent, 'failed_count' => $failed,
                        'blocked_count' => $blocked, 'last_user_id' => $last,
                    ]);
                    if ($onProgress) {
                        $onProgress($sent, $failed, $blocked, $broadcast->total_users);
                    }
                }

                usleep(40000); // ~25 сообщений/сек
            }

            // Пауза между батчами + проверка паузы
            sleep(1);
            $broadcast->refresh();
            if ($broadcast->status === 'paused') {
                $broadcast->update(['sent_count' => $sent, 'failed_count' => $failed, 'blocked_count' => $blocked, 'last_user_id' => $last]);

                return ['status' => 'paused', 'sent' => $sent, 'failed' => $failed, 'blocked' => $blocked];
            }
        }

        $broadcast->update([
            'sent_count' => $sent, 'failed_count' => $failed, 'blocked_count' => $blocked,
            'last_user_id' => $last, 'status' => 'completed', 'completed_at' => now(),
        ]);

        return ['status' => 'completed', 'sent' => $sent, 'failed' => $failed, 'blocked' => $blocked];
    }

    /**
     * Отправить одному пользователю по выбранным каналам.
     * $row — объект с полями user_id, chat_id.
     */
    protected function sendToUser(Broadcast $broadcast, object $row, array $channels): string
    {
        $userId = (int) $row->user_id;
        $isMax = $userId >= self::MAX_USER_ID_OFFSET;
        $sentAny = false;
        $tgFailed = false;

        // Telegram — только реальные TG-пользователи
        if (in_array('telegram', $channels, true) && ! $isMax && $broadcast->text_content) {
            if ($this->telegram->sendMessage($userId, $broadcast->text_content)) {
                $sentAny = true;
            } else {
                $tgFailed = true;
            }
        }

        // MAX — только MAX-пользователи со стораженным chat_id
        if (in_array('max', $channels, true) && $isMax && $row->chat_id && $broadcast->text_content) {
            if ($this->max->sendMessage($row->chat_id, $broadcast->text_content, 'html')) {
                $sentAny = true;
            }
        }

        // Веб-уведомление — для всех
        if (in_array('web', $channels, true)) {
            if ($this->createWebNotification($userId, $broadcast->web_title, $broadcast->web_message ?: $broadcast->text_content, $broadcast->id)) {
                $sentAny = true;
            }
        }

        if ($sentAny) {
            return 'sent';
        }

        return $tgFailed ? 'blocked' : 'failed';
    }

    /**
     * Тестовая отправка одному пользователю (по его user_id).
     * Возвращает результат по каналам. is_blocked игнорируется.
     */
    public function sendTest(array $channels, ?string $text, ?string $webTitle, ?string $webMessage, int $userId): array
    {
        $row = DB::table('users')->where('user_id', $userId)->first(['user_id', 'chat_id']);
        if (! $row) {
            return ['ok' => false, 'error' => "Пользователь {$userId} не найден в базе"];
        }

        $isMax = $userId >= self::MAX_USER_ID_OFFSET;
        $results = [];

        if (in_array('telegram', $channels, true)) {
            if ($isMax) {
                $results['telegram'] = 'skip: это MAX-пользователь';
            } elseif (! $text) {
                $results['telegram'] = 'skip: пустой текст';
            } else {
                $results['telegram'] = $this->telegram->sendMessage($userId, $text) ? 'ok' : 'ошибка (заблокировал бота?)';
            }
        }

        if (in_array('max', $channels, true)) {
            if (! $isMax) {
                $results['max'] = 'skip: это Telegram-пользователь';
            } elseif (! $row->chat_id) {
                $results['max'] = 'skip: нет chat_id (не писал боту)';
            } elseif (! $text) {
                $results['max'] = 'skip: пустой текст';
            } else {
                $results['max'] = $this->max->sendMessage($row->chat_id, $text, 'html') ? 'ok' : 'ошибка MAX API';
            }
        }

        if (in_array('web', $channels, true)) {
            $results['web'] = $this->createWebNotification($userId, $webTitle, $webMessage ?: $text, null) ? 'ok' : 'ошибка';
        }

        return ['ok' => true, 'results' => $results];
    }

    /**
     * Создать веб-уведомление с сохранением форматирования и переносов строк.
     * Раньше здесь был strip_tags(), из-за чего текст приходил в одну строку.
     */
    protected function createWebNotification(int $userId, ?string $title, ?string $message, ?int $broadcastId): bool
    {
        $title = $title ?: 'Уведомление';
        $message = $message ?: '';

        if ($title === '' && $message === '') {
            return false;
        }

        try {
            WebNotification::create([
                'user_id' => $userId,
                'broadcast_id' => $broadcastId,
                'title' => $title,
                'message' => $this->formatWebMessage($message),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning("Web notification failed for user {$userId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Санитизация текста веб-уведомления: оставляем безопасные теги
     * форматирования и превращаем переносы строк в <br>.
     */
    public function formatWebMessage(string $raw): string
    {
        $allowed = '<b><strong><i><em><u><s><br><a>';
        $clean = strip_tags($raw, $allowed);

        // Переносы строк → <br>, но не плодим <br> рядом с уже существующими
        $clean = preg_replace('/\r\n|\r|\n/', "\n", $clean);

        return nl2br($clean, false);
    }
}
