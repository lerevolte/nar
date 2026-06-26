<?php

namespace App\Http\Controllers;

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
     * Страница рассылок
     */
    public function index(Request $request)
    {
        if (! $this->isAdmin($request)) {
            abort(403);
        }

        $broadcasts = Broadcast::orderByDesc('created_at')->take(20)->get();

        return view('admin.broadcast.index', compact('broadcasts'));
    }

    /**
     * API: подсчёт пользователей по сегменту
     */
    public function countSegment(Request $request, BroadcastService $service)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate(['segment' => 'required|string|in:all,inactive_mix,paid,test']);

        $count = $service->countBySegment($request->input('segment'));

        return response()->json(['count' => $count]);
    }

    /**
     * API: создать и запустить рассылку
     */
    public function create(Request $request, BroadcastService $service)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'segment' => 'required|string|in:all,inactive_mix,paid,test',
            'channel' => 'required|string|in:telegram,web,both',
            'text_content' => 'nullable|string|max:4000',
            'web_title' => 'nullable|string|max:255',
            'web_message' => 'nullable|string|max:4000',
        ]);

        $user = $request->get('auth_user');

        // Валидация: хотя бы одно из полей должно быть заполнено
        $channel = $request->input('channel');
        $textContent = $request->input('text_content');
        $webTitle = $request->input('web_title');
        $webMessage = $request->input('web_message');

        if (in_array($channel, ['telegram', 'both']) && empty($textContent)) {
            return response()->json(['error' => 'Для Telegram-рассылки нужен текст сообщения'], 400);
        }

        if (in_array($channel, ['web', 'both']) && empty($webTitle) && empty($webMessage)) {
            return response()->json(['error' => 'Для веб-уведомления нужен заголовок или текст'], 400);
        }

        $broadcast = $service->createBroadcast([
            'admin_id' => $user->user_id,
            'segment' => $request->input('segment'),
            'channel' => $channel,
            'text_content' => $textContent,
            'web_title' => $webTitle,
            'web_message' => $webMessage ?: $textContent,
        ]);

        return response()->json([
            'success' => true,
            'broadcast_id' => $broadcast->id,
            'total_users' => $broadcast->total_users,
            'message' => "Рассылка #{$broadcast->id} создана. Запусти: php artisan broadcast:run {$broadcast->id}",
        ]);
    }

    /**
     * API: статус рассылки
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
     * API: поставить на паузу
     */
    public function pause(Request $request, $id)
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $broadcast = Broadcast::findOrFail($id);
        $broadcast->update(['status' => 'paused']);

        return response()->json(['success' => true, 'message' => 'Рассылка поставлена на паузу']);
    }

    // ==========================================
    // WEB NOTIFICATIONS API (для всех юзеров)
    // ==========================================

    /**
     * API: получить непрочитанные уведомления
     */
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

    /**
     * API: пометить как прочитанное
     */
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
