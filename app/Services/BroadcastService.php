<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\User;
use App\Models\WebNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastService
{
    protected TelegramNotificationService $telegram;

    public function __construct(TelegramNotificationService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Получить user_id по сегменту (возвращает query)
     */
    public function getUsersBySegment(string $segment)
    {
        $query = DB::table('users')->where('is_blocked', 0);

        switch ($segment) {
            case 'all':
                break;

            case 'inactive_mix':
                $inactiveIds = DB::table('users')
                    ->where('is_blocked', 0)
                    ->where('last_activity', '<', now()->subDays(7))
                    ->pluck('user_id');

                $draftIds = DB::table('drafts as d')
                    ->leftJoin('songs as s', 'd.user_id', '=', 's.user_id')
                    ->join('users as u', 'd.user_id', '=', 'u.user_id')
                    ->whereNull('s.id')
                    ->where('u.is_blocked', 0)
                    ->where('d.updated_at', '<', now()->subDays(7))
                    ->pluck('d.user_id');

                $churnIds = DB::table('users as u')
                    ->join('payments as p', 'u.user_id', '=', 'p.user_id')
                    ->where('u.is_blocked', 0)
                    ->where('p.status', 'succeeded')
                    ->groupBy('u.user_id')
                    ->havingRaw('MAX(p.created_at) < ?', [now()->subDays(10)])
                    ->pluck('u.user_id');

                $allIds = $inactiveIds->merge($draftIds)->merge($churnIds)->unique();
                $query = DB::table('users')->whereIn('user_id', $allIds);
                break;

            case 'paid':
                $paidIds = DB::table('payments')
                    ->where('status', 'succeeded')
                    ->distinct()
                    ->pluck('user_id');

                $query = DB::table('users')
                    ->where('is_blocked', 0)
                    ->whereIn('user_id', $paidIds);
                break;

            case 'test':
                $query = DB::table('users')->where('user_id', 288559694);
                break;
        }

        return $query;
    }

    /**
     * Подсчитать пользователей в сегменте
     */
    public function countBySegment(string $segment): int
    {
        return $this->getUsersBySegment($segment)->count();
    }

    /**
     * Создать рассылку
     */
    public function createBroadcast(array $data): Broadcast
    {
        $segment = $data['segment'] ?? 'all';
        $totalUsers = $this->countBySegment($segment);

        return Broadcast::create([
            'admin_id' => $data['admin_id'],
            'type' => 'text',
            'channel' => $data['channel'] ?? 'telegram',
            'status' => 'pending',
            'segment' => $segment,
            'total_users' => $totalUsers,
            'text_content' => $data['text_content'] ?? null,
            'web_title' => $data['web_title'] ?? null,
            'web_message' => $data['web_message'] ?? null,
        ]);
    }

    /**
     * Запустить рассылку (из artisan-команды)
     */
    public function runBroadcast(int $broadcastId, callable $onProgress = null): array
    {
        $broadcast = Broadcast::findOrFail($broadcastId);
        $broadcast->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $channel = $broadcast->channel ?? 'telegram';

        $userIds = $this->getUsersBySegment($broadcast->segment)
            ->where('user_id', '>', $broadcast->last_user_id ?: 0)
            ->orderBy('user_id')
            ->pluck('user_id');

        $sent = $broadcast->sent_count;
        $failed = $broadcast->failed_count;
        $blocked = $broadcast->blocked_count;
        $batchCount = 0;

        foreach ($userIds as $userId) {
            // Проверяем паузу каждые 50 сообщений
            if ($batchCount > 0 && $batchCount % 50 === 0) {
                $broadcast->refresh();
                if ($broadcast->status === 'paused') {
                    break;
                }
            }

            $result = $this->sendToUser($broadcast, $userId, $channel);

            if ($result === 'sent') {
                $sent++;
            } elseif ($result === 'blocked') {
                $blocked++;
                DB::table('users')->where('user_id', $userId)->update(['is_blocked' => 1]);
            } else {
                $failed++;
            }

            $batchCount++;

            // Сохраняем прогресс каждые 20 сообщений
            if ($batchCount % 20 === 0) {
                $broadcast->update([
                    'sent_count' => $sent,
                    'failed_count' => $failed,
                    'blocked_count' => $blocked,
                    'last_user_id' => $userId,
                ]);

                if ($onProgress) {
                    $onProgress($sent, $failed, $blocked, $broadcast->total_users);
                }
            }

            // Задержка 40ms (~25 сообщений/сек)
            usleep(40000);

            // Доп. пауза каждые 50 сообщений (1 сек)
            if ($batchCount % 50 === 0) {
                sleep(1);
            }
        }

        // Финальное обновление
        $finalStatus = $broadcast->fresh()->status === 'paused' ? 'paused' : 'completed';
        $broadcast->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'blocked_count' => $blocked,
            'status' => $finalStatus,
            'completed_at' => $finalStatus === 'completed' ? now() : null,
        ]);

        return [
            'status' => $finalStatus,
            'sent' => $sent,
            'failed' => $failed,
            'blocked' => $blocked,
        ];
    }

    /**
     * Отправить одному пользователю
     */
    protected function sendToUser(Broadcast $broadcast, int $userId, string $channel): string
    {
        $sentAny = false;

        // Telegram
        if (in_array($channel, ['telegram', 'both'])) {
            if ($broadcast->text_content) {
                $result = $this->telegram->sendMessage($userId, $broadcast->text_content);
                if ($result) {
                    $sentAny = true;
                } else {
                    return 'blocked';
                }
            }
        }

        // Web notification
        if (in_array($channel, ['web', 'both'])) {
            $title = $broadcast->web_title ?: 'Уведомление';
            $message = $broadcast->web_message ?: $broadcast->text_content ?: '';

            if ($title || $message) {
                try {
                    WebNotification::create([
                        'user_id' => $userId,
                        'broadcast_id' => $broadcast->id,
                        'title' => $title,
                        'message' => strip_tags($message),
                    ]);
                    $sentAny = true;
                } catch (\Exception $e) {
                    Log::warning("Web notification failed for user {$userId}: " . $e->getMessage());
                }
            }
        }

        return $sentAny ? 'sent' : 'failed';
    }
}