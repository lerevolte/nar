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
     *  - partition=true — входит в строгое непересекающееся разбиение (карточки дашборда).
     *    Каждый активный пользователь попадает ровно в один partition-сегмент.
     *  - partition=false — «широкие» пересекающиеся сегменты (all/paid) для массовых анонсов.
     *  - template — готовый HTML-шаблон письма. [ПРОМОКОД] замени на свой код.
     */
    public static function segments(): array
    {
        return [
            'no_create' => [
                'emoji' => '🫥', 'label' => 'Подписался — ничего не создал', 'partition' => true, 'sendable' => true,
                'desc' => 'Нет оплаты, нет черновика, нет песни. Не сделал ни одного шага.',
                'action' => 'Онбординг: покажи, как создать первую песню за 1 минуту. Один чёткий CTA «Создать песню», без скидок (рано).',
                'template' => "Привет! 👋\n\nТы уже с нами, но ещё <b>не создал ни одной песни</b> 🎵\n\nЭто занимает всего <b>1 минуту</b>: выбираешь повод и стиль — а нейросеть пишет текст и музыку за тебя.\n\n👉 Открой бота и нажми «🎵 Создать песню» — попробуй бесплатно!",
            ],
            'draft' => [
                'emoji' => '📝', 'label' => 'Черновик без готовой песни', 'partition' => true, 'sendable' => true,
                'desc' => 'Нет оплаты, есть черновик, но нет готовой песни. Застрял в процессе.',
                'action' => 'Напоминание «допиши свою песню» — он уже вложился, дожать до генерации. Скидка не нужна, нужен толчок.',
                'template' => "У тебя остался <b>незаконченный черновик</b> 📝\n\nТы уже начал создавать песню — осталось совсем чуть-чуть, чтобы получить готовый трек 🎶\n\n👉 Вернись в бот и заверши — это займёт минуту.",
            ],
            'no_pay' => [
                'emoji' => '🎧', 'label' => 'Есть песня, но не оплатил', 'partition' => true, 'sendable' => true,
                'desc' => 'Нет успешной оплаты, но есть сгенерированная песня. Самый горячий сегмент для конверсии.',
                'action' => 'Оффер со скидкой/промокодом, чтобы забрать полную версию. Здесь промокод работает лучше всего.',
                'template' => "Твоя песня почти готова! 🎧\n\nОсталось забрать полную версию в хорошем качестве. Держи <b>скидку по промокоду</b>:\n\n🎁 <b>[ПРОМОКОД]</b>\n\n👉 Введи его в боте при оплате — предложение ограничено!",
            ],
            'churn' => [
                'emoji' => '💔', 'label' => 'Оплачивал, но пропал (отток)', 'partition' => true, 'sendable' => true,
                'desc' => 'Есть успешная оплата, но неактивен более '.self::CHURN_DAYS.' дней. Ценные ушедшие клиенты.',
                'action' => 'Реактивация: новинки + бонус на следующую песню. Они уже платили — верни их выгодным поводом.',
                'template' => "Мы скучаем! 💔\n\nУ нас появились новые стили и функции — самое время вернуться и создать новый хит 🔥\n\nДарим бонус на следующую песню:\n🎁 <b>[ПРОМОКОД]</b>\n\n👉 Возвращайся в бот!",
            ],
            'paid_active' => [
                'emoji' => '💚', 'label' => 'Оплатившие и активные', 'partition' => true, 'sendable' => true,
                'desc' => 'Есть успешная оплата и активность за последние '.self::CHURN_DAYS.' дней. Лояльное ядро.',
                'action' => 'Апселл, новые форматы, реферальная программа («приведи друга — получи песню»). Скидки не нужны.',
                'template' => "Спасибо, что ты с нами! 🎉\n\n<b>[Здесь расскажи о новинке / акции / реферальной программе]</b>\n\nНапример: приведи друга по своей ссылке и получи бонусную песню 🎁",
            ],
            'blocked' => [
                'emoji' => '🚫', 'label' => 'Заблокировали бота', 'partition' => true, 'sendable' => false,
                'desc' => 'is_blocked = 1. Доставка в мессенджер невозможна.',
                'action' => 'Слать нельзя. Показано только для контроля оттока.',
                'template' => null,
            ],
            'paid' => [
                'emoji' => '💰', 'label' => 'Все оплатившие (широкий)', 'partition' => false, 'sendable' => true,
                'desc' => 'Хотя бы одна успешная оплата. Пересекается с churn + paid_active.',
                'action' => 'Массовые апселл-анонсы для всех клиентов.',
                'template' => null,
            ],
            'all' => [
                'emoji' => '🌍', 'label' => 'Все активные (широкий)', 'partition' => false, 'sendable' => true,
                'desc' => 'Все незаблокированные. Пересекается со всеми сегментами.',
                'action' => 'Глобальные анонсы для всей базы.',
                'template' => null,
            ],
        ];
    }

    public static function isValidSegment(string $segment): bool
    {
        $base = str_ends_with($segment, ':inactive') ? substr($segment, 0, -strlen(':inactive')) : $segment;

        return array_key_exists($base, self::segments()) || in_array($base, ['sleep', 'inactive_mix', 'test'], true);
    }

    /**
     * Query по сегменту с учётом суффикса ":inactive" (только неактивные 14д+).
     * Через этот метод ходят все подсчёты и рассылка.
     */
    public function segmentQuery(string $segmentRaw): Builder
    {
        $onlyInactive = str_ends_with($segmentRaw, ':inactive');
        $key = $onlyInactive ? substr($segmentRaw, 0, -strlen(':inactive')) : $segmentRaw;

        $query = $this->getUsersBySegment($key);

        if ($onlyInactive) {
            $query->where('last_activity', '<', now()->subDays(self::SLEEP_DAYS));
        }

        return $query;
    }

    /**
     * Базовый query пользователей по сегменту (строгое непересекающееся разбиение).
     *   P=есть успешная оплата, S=есть песня, D=есть черновик, I=неактивен CHURN_DAYS+
     *   no_create=¬P∧¬S∧¬D · draft=¬P∧¬S∧D · no_pay=¬P∧S · churn=P∧I · paid_active=P∧¬I
     */
    public function getUsersBySegment(string $segment): Builder
    {
        $paidExists = fn ($q) => $q->from('payments')
            ->whereColumn('payments.user_id', 'users.user_id')
            ->where('payments.status', 'succeeded');
        $songExists = fn ($q) => $q->from('songs')->whereColumn('songs.user_id', 'users.user_id');
        $draftExists = fn ($q) => $q->from('drafts')->whereColumn('drafts.user_id', 'users.user_id');
        $churnCutoff = now()->subDays(self::CHURN_DAYS);

        switch ($segment) {
            case 'no_create': // ¬P ∧ ¬S ∧ ¬D
                return DB::table('users')->where('is_blocked', 0)
                    ->whereNotExists($paidExists)
                    ->whereNotExists($songExists)
                    ->whereNotExists($draftExists);

            case 'draft': // ¬P ∧ ¬S ∧ D
                return DB::table('users')->where('is_blocked', 0)
                    ->whereNotExists($paidExists)
                    ->whereNotExists($songExists)
                    ->whereExists($draftExists);

            case 'no_pay': // ¬P ∧ S
                return DB::table('users')->where('is_blocked', 0)
                    ->whereNotExists($paidExists)
                    ->whereExists($songExists);

            case 'churn': // P ∧ I (неактивен)
                return DB::table('users')->where('is_blocked', 0)
                    ->whereExists($paidExists)
                    ->where('last_activity', '<', $churnCutoff);

            case 'paid_active': // P ∧ ¬I (активен либо last_activity пуст)
                return DB::table('users')->where('is_blocked', 0)
                    ->whereExists($paidExists)
                    ->where(fn ($w) => $w->where('last_activity', '>=', $churnCutoff)->orWhereNull('last_activity'));

            case 'paid': // широкий: все оплатившие (пересекается)
                return DB::table('users')->where('is_blocked', 0)->whereExists($paidExists);

            case 'sleep': // legacy: неактивные любой стадии
                return DB::table('users')->where('is_blocked', 0)
                    ->where('last_activity', '<', now()->subDays(self::SLEEP_DAYS));

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
        $q = $this->segmentQuery($segment);
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
        $base = $this->segmentQuery($segment);

        $total = (clone $base)->count();
        $tg = (clone $base)->where('user_id', '<', $off)->count();
        $maxAll = (clone $base)->where('user_id', '>=', $off)->count();
        $maxReach = (clone $base)->where('user_id', '>=', $off)->whereNotNull('chat_id')->count();
        $inactive = (clone $base)->where('last_activity', '<', now()->subDays(self::SLEEP_DAYS))->count();

        return ['total' => $total, 'tg' => $tg, 'max' => $maxAll, 'max_reachable' => $maxReach, 'inactive' => $inactive];
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
            $rows = $this->applyChannelFilter($this->segmentQuery($segment), $channels)
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
