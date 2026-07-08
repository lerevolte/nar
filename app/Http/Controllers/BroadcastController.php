<?php

namespace App\Http\Controllers;

use App\Jobs\RunBroadcastJob;
use App\Models\Broadcast;
use App\Models\WebNotification;
use App\Services\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    private const ADMIN_IDS = [288559694, 154483653];

    private function isAdmin(Request $request): bool
    {
        $user = $request->get('auth_user');

        return $user && in_array($user->user_id, self::ADMIN_IDS);
    }

    /**
     * Страница рассылок + дашборд сегментов.
     */
    public function index(Request $request)
    {
        if (! $this->isAdmin($request)) {
            abort(403);
        }

        $broadcasts = Broadcast::orderByDesc('created_at')->take(20)->get();
        $segments = BroadcastService::segments();
        $maxConfigured = (string) config('max.bot_token', '') !== '';

        return view('admin.broadcast.index', compact('broadcasts', 'segments', 'maxConfigured'));
    }

    /**
     * API: метаданные + разбивка по всем сегментам (для дашборда).
     */
    public function segments(Request $request, BroadcastService $service)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $out = [];
        foreach (BroadcastService::segments() as $key => $meta) {
            $out[] = array_merge(['key' => $key], $meta, ['breakdown' => $service->segmentBreakdown($key)]);
        }

        return response()->json(['segments' => $out]);
    }

    /**
     * API: подсчёт получателей по сегменту с учётом выбранных каналов.
     */
    public function countSegment(Request $request, BroadcastService $service)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'segment' => 'required|string',
            'channels' => 'nullable|array',
            'channels.*' => 'in:telegram,max,web',
        ]);

        if (! BroadcastService::isValidSegment($data['segment'])) {
            return response()->json(['error' => 'Неизвестный сегмент'], 422);
        }

        $channels = $data['channels'] ?? null;

        return response()->json([
            'count' => $service->countBySegment($data['segment'], $channels),
            'breakdown' => $service->segmentBreakdown($data['segment']),
        ]);
    }

    /**
     * API: создать рассылку и (по умолчанию) сразу запустить через очередь.
     */
    public function create(Request $request, BroadcastService $service)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'segment' => 'required|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:telegram,max,web',
            'text_content' => 'nullable|string|max:4000',
            'web_title' => 'nullable|string|max:255',
            'web_message' => 'nullable|string|max:4000',
            'start' => 'nullable|boolean',
        ]);

        if (! BroadcastService::isValidSegment($data['segment'])) {
            return response()->json(['error' => 'Неизвестный сегмент'], 422);
        }

        $channels = $data['channels'];
        $needsText = array_intersect($channels, ['telegram', 'max']) !== [];

        if ($needsText && empty($data['text_content'])) {
            return response()->json(['error' => 'Для Telegram/MAX нужен текст сообщения'], 422);
        }
        if (in_array('web', $channels, true) && empty($data['web_title']) && empty($data['web_message']) && empty($data['text_content'])) {
            return response()->json(['error' => 'Для веб-уведомления нужен заголовок или текст'], 422);
        }

        $user = $request->get('auth_user');

        $broadcast = $service->createBroadcast([
            'admin_id' => $user->user_id,
            'segment' => $data['segment'],
            'channels' => $channels,
            'text_content' => $data['text_content'] ?? null,
            'web_title' => $data['web_title'] ?? null,
            'web_message' => $data['web_message'] ?? $data['text_content'] ?? null,
        ]);

        $started = false;
        if ($data['start'] ?? true) {
            RunBroadcastJob::dispatch($broadcast->id);
            $started = true;
        }

        return response()->json([
            'success' => true,
            'broadcast_id' => $broadcast->id,
            'total_users' => $broadcast->total_users,
            'started' => $started,
            'message' => $started
                ? "Рассылка #{$broadcast->id} запущена ({$broadcast->total_users} получателей)"
                : "Рассылка #{$broadcast->id} создана. Запусти кнопкой или: php artisan broadcast:run {$broadcast->id}",
        ]);
    }

    /**
     * API: запустить существующую (pending/paused) рассылку из интерфейса.
     */
    public function start(Request $request, $id)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $broadcast = Broadcast::findOrFail($id);

        if ($broadcast->status === 'completed') {
            return response()->json(['error' => 'Рассылка уже завершена'], 422);
        }
        if ($broadcast->status === 'running') {
            return response()->json(['error' => 'Рассылка уже выполняется'], 422);
        }

        $broadcast->update(['status' => 'pending']);
        RunBroadcastJob::dispatch($broadcast->id);

        return response()->json(['success' => true, 'message' => "Рассылка #{$broadcast->id} запущена"]);
    }

    /**
     * API: тестовая отправка одному пользователю по его идентификатору.
     */
    public function test(Request $request, BroadcastService $service)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'user_id' => 'required|integer',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:telegram,max,web',
            'text_content' => 'nullable|string|max:4000',
            'web_title' => 'nullable|string|max:255',
            'web_message' => 'nullable|string|max:4000',
        ]);

        $result = $service->sendTest(
            $data['channels'],
            $data['text_content'] ?? null,
            $data['web_title'] ?? null,
            $data['web_message'] ?? $data['text_content'] ?? null,
            (int) $data['user_id'],
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка'], 422);
        }

        return response()->json(['success' => true, 'results' => $result['results']]);
    }

    /**
     * API: статус рассылки (для live-прогресса).
     */
    public function status(Request $request, $id)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $broadcast = Broadcast::findOrFail($id);

        return response()->json([
            'id' => $broadcast->id,
            'status' => $broadcast->status,
            'channel' => $broadcast->channel,
            'segment' => $broadcast->segment,
            'total_users' => $broadcast->total_users,
            'sent_count' => $broadcast->sent_count,
            'failed_count' => $broadcast->failed_count,
            'blocked_count' => $broadcast->blocked_count,
            'progress' => $broadcast->progressPercent(),
        ]);
    }

    /**
     * API: поставить на паузу.
     */
    public function pause(Request $request, $id)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $broadcast = Broadcast::findOrFail($id);
        $broadcast->update(['status' => 'paused']);

        return response()->json(['success' => true, 'message' => 'Рассылка ставится на паузу']);
    }

    // ==========================================
    // WEB NOTIFICATIONS API (для всех юзеров)
    // ==========================================

    public function getNotifications(Request $request)
    {
        $user = $request->get('auth_user');

        $notifications = WebNotification::where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at?->format('d.m.Y H:i'),
                'created_at_ts' => $n->created_at?->timestamp,
            ]);

        $unreadCount = WebNotification::where('user_id', $user->user_id)
            ->where('is_read', 0)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request)
    {
        $user = $request->get('auth_user');

        $request->validate([
            'notification_id' => 'nullable|integer',
            'notification_ids' => 'nullable|array',
            'notification_ids.*' => 'integer',
            'mark_all' => 'nullable|boolean',
        ]);

        if ($request->input('mark_all')) {
            WebNotification::where('user_id', $user->user_id)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        } elseif ($request->filled('notification_ids')) {
            WebNotification::whereIn('id', $request->input('notification_ids'))
                ->where('user_id', $user->user_id)
                ->update(['is_read' => 1]);
        } elseif ($request->input('notification_id')) {
            WebNotification::where('id', $request->input('notification_id'))
                ->where('user_id', $user->user_id)
                ->update(['is_read' => 1]);
        }

        return response()->json(['success' => true]);
    }
}
