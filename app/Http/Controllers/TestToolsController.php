<?php

namespace App\Http\Controllers;

use App\Mail\AccountCredentialsMail;
use App\Mail\ResetPasswordMail;
use App\Mail\SongFailedMail;
use App\Mail\SongReadyMail;
use App\Models\GuestOrder;
use App\Models\Song;
use App\Models\User;
use App\Services\GuestOrderService;
use App\Services\SunoService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Админ-панель тестовых сценариев (без реальной оплаты).
 * Доступна ТОЛЬКО одному админу.
 */
class TestToolsController extends Controller
{
    /** Единственный админ, которому доступны тестовые инструменты */
    private const TEST_ADMIN_ID = 288559694;

    private function ensureAdmin(Request $request): void
    {
        $user = $request->get('auth_user');

        if (! $user || (int) $user->user_id !== self::TEST_ADMIN_ID) {
            abort(403);
        }
    }

    private function admin(Request $request): User
    {
        return $request->get('auth_user');
    }

    /**
     * Страница тестовых инструментов
     */
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        return view('admin.test-tools', [
            'defaultEmail' => $this->admin($request)->email,
        ]);
    }

    /**
     * Сценарий 1: успешная оплата без ЮKassa.
     * Завершает гостевой заказ как webhook: баланс, генерация, письмо, уведомление админу.
     */
    public function pay(Request $request, GuestOrderService $orderService, SunoService $sunoService)
    {
        $this->ensureAdmin($request);

        $email = trim((string) $request->input('email')) ?: $this->admin($request)->email;

        if (! $email) {
            return response()->json(['ok' => false, 'error' => 'Укажи email'], 422);
        }

        $order = $this->makeTestOrder($email, $request);

        // Полный путь как в вебхуке (handlePaymentSucceeded -> startGeneration)
        $paid = $orderService->handlePaymentSucceeded($order);

        if (! ($paid['success'] ?? false)) {
            return response()->json(['ok' => false, 'error' => $paid['error'] ?? 'Не удалось завершить заказ'], 500);
        }

        $gen = $orderService->startGeneration($order->fresh(), $sunoService);

        return response()->json([
            'ok' => true,
            'message' => 'Заказ завершён без оплаты. Открой страницу успеха — пойдёт реальная генерация и письма.',
            'generation' => $gen,
            'success_url' => route('public.generate.success', ['token' => $order->token]),
        ]);
    }

    /**
     * Сценарий 2: ошибка генерации + возврат на баланс.
     * Создаёт оплаченный заказ с песней и сразу симулирует провал генерации
     * (без вызова Suno) — возврат, письмо об ошибке, статус failed для кнопки «Повторить».
     */
    public function fail(Request $request)
    {
        $this->ensureAdmin($request);

        $admin = $this->admin($request);
        $email = trim((string) $request->input('email')) ?: $admin->email;

        $order = $this->makeTestOrder($email, $request);

        // Помечаем оплаченным от имени админа (paid_at нужен для кнопки «Повторить»)
        $order->update([
            'status' => 'generating',
            'user_id' => $admin->user_id,
            'paid_at' => now(),
        ]);

        $song = Song::create([
            'user_id' => $admin->user_id,
            'title' => $order->title,
            'occasion' => $order->occasion,
            'genre' => $order->genre,
            'description' => $order->description,
            'lyrics' => $order->lyrics,
            'is_deleted' => 0,
            'plays_count' => 0,
        ]);

        $order->update(['song_id' => $song->id]);

        // Симуляция провала генерации = та же логика, что в CheckSongGenerationStatus::refundAndNotify
        $admin->increment('balance', 1);
        $song->update(['is_deleted' => 1, 'refunded_at' => now()]);
        $order->update(['status' => 'failed']);

        $telegram = app(TelegramNotificationService::class);
        try {
            $telegram->sendMessage(
                $admin->user_id,
                "❌ [ТЕСТ] Генерация песни «{$song->title}» не удалась. Мы вернули 1 песню на твой баланс — попробуй ещё раз."
            );
        } catch (\Exception $e) {
            Log::error('Test fail telegram notify failed: '.$e->getMessage());
        }

        $mailSent = false;
        if ($email) {
            try {
                $retryUrl = route('public.generate.success', ['token' => $order->token]);
                Mail::to($email)->send(new SongFailedMail((string) $song->title, $retryUrl));
                $mailSent = true;
            } catch (\Exception $e) {
                Log::error('Test SongFailedMail failed: '.$e->getMessage());

                return response()->json(['ok' => false, 'error' => 'Письмо не отправилось: '.$e->getMessage()], 500);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Сгенерирована ошибка: 1 песня возвращена на баланс'.($mailSent ? ', письмо отправлено' : '').'. Открой страницу — увидишь кнопку «Повторить».',
            'success_url' => route('public.generate.success', ['token' => $order->token]),
        ]);
    }

    /**
     * Сценарий 3: проверка SMTP — отправить все 4 письма синхронно на адрес.
     */
    public function mail(Request $request)
    {
        $this->ensureAdmin($request);

        $email = trim((string) $request->input('email')) ?: $this->admin($request)->email;

        if (! $email) {
            return response()->json(['ok' => false, 'error' => 'Укажи email'], 422);
        }

        $base = rtrim((string) config('app.url'), '/');

        $letters = [
            'Доступы (новый юзер)' => new AccountCredentialsMail($email, 'Music1234', 'Тестовая песня'),
            'Песня готова' => new SongReadyMail('Тестовая песня', $base.'/music/test1.mp3', $base.'/music/test2.mp3'),
            'Ошибка генерации' => new SongFailedMail('Тестовая песня', $base.'/create-song'),
            'Сброс пароля' => new ResetPasswordMail($base.'/reset-password/test-token?email='.urlencode($email)),
        ];

        $results = [];
        foreach ($letters as $name => $mailable) {
            try {
                Mail::to($email)->send($mailable);
                $results[$name] = 'отправлено';
            } catch (\Exception $e) {
                $results[$name] = 'ОШИБКА: '.$e->getMessage();
            }
        }

        return response()->json([
            'ok' => ! collect($results)->contains(fn ($r) => str_starts_with($r, 'ОШИБКА')),
            'email' => $email,
            'results' => $results,
        ]);
    }

    /**
     * Сценарий 4: восстановление пароля — отправить реальную ссылку сброса.
     */
    public function reset(Request $request)
    {
        $this->ensureAdmin($request);

        $email = trim((string) $request->input('email')) ?: $this->admin($request)->email;

        if (! $email) {
            return response()->json(['ok' => false, 'error' => 'Укажи email'], 422);
        }

        if (! User::where('email', $email)->exists()) {
            return response()->json([
                'ok' => false,
                'error' => 'Пользователя с таким email нет — сброс работает только для существующих аккаунтов с email.',
            ], 422);
        }

        $status = Password::sendResetLink(['email' => $email]);

        return response()->json([
            'ok' => $status === Password::RESET_LINK_SENT,
            'status' => $status,
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Письмо со ссылкой сброса отправлено на '.$email
                : 'Не удалось отправить: '.$status,
        ]);
    }

    /**
     * Создаёт тестовый гостевой заказ (без user_id — чтобы отработал путь нового/существующего юзера по email).
     */
    private function makeTestOrder(string $email, Request $request): GuestOrder
    {
        return GuestOrder::create([
            'token' => Str::random(48),
            'contact' => $email,
            'contact_type' => 'email',
            'first_name' => 'Тест',
            'title' => $request->input('title') ?: 'Тестовая песня',
            'lyrics' => $request->input('lyrics') ?: "[Verse]\nЭто тестовая песня для проверки\nГенерация без оплаты идёт\n\n[Chorus]\nВсё работает, всё хорошо\nНа репите играет легко",
            'genre' => $request->input('genre') ?: 'pop',
            'artist' => null,
            'vocal_gender' => 'f',
            'language' => 'ru',
            'amount' => 199,
            'status' => 'pending_payment',
            'payment_id' => 'TEST-'.Str::random(12),
        ]);
    }
}
