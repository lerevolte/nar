<?php

namespace App\Services;

use App\Jobs\CheckSongGenerationStatus;
use App\Mail\AccountCredentialsMail;
use App\Models\GuestOrder;
use App\Models\Payment;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GuestOrderService
{
    /**
     * Обработка успешной оплаты гостевого заказа.
     * Идемпотентна — повторные вызовы безопасны.
     *
     * @return array ['success' => bool, 'user_id' => int|null, 'is_new_user' => bool, 'plain_password' => string|null, 'error' => string|null]
     */
    public function handlePaymentSucceeded(GuestOrder $order): array
    {
        // Идемпотентность: если уже обработан — возвращаем успех
        if ($order->isPaid() && $order->user_id) {
            return [
                'success' => true,
                'user_id' => $order->user_id,
                'is_new_user' => false,
                'plain_password' => null,
            ];
        }

        try {
            DB::beginTransaction();

            // 1. Найти или создать пользователя
            [$user, $isNewUser, $plainPassword] = $this->findOrCreateUser($order);

            // 2. Обновить заказ
            $order->update([
                'status' => 'paid',
                'user_id' => $user->user_id,
                'paid_at' => now(),
            ]);

            // 3. Записать Payment
            $this->recordPayment($order, $user);

            // 3.5. Стешим пароль в кеше на 15 минут, если это новый юзер
            if ($isNewUser && $plainPassword) {
                \Illuminate\Support\Facades\Cache::put(
                    "guest_credentials:{$order->token}",
                    [
                        'password' => $plainPassword,
                        'login' => $order->contact,
                        'is_new' => true,
                    ],
                    now()->addMinutes(15)
                );
            }

            // 4. Начислить 1 песню на баланс
            $user->increment('balance', 1);

            DB::commit();

            Log::info('Guest order paid & user created/linked', [
                'order_id' => $order->id,
                'token' => $order->token,
                'user_id' => $user->user_id,
                'is_new_user' => $isNewUser,
                'contact_type' => $order->contact_type,
            ]);

            // Письмо с доступами в ЛК (только если контакт — email).
            // Не должно ронять обработку оплаты.
            if ($order->contact_type === 'email' && $order->contact) {
                try {
                    Mail::to($order->contact)->queue(new AccountCredentialsMail(
                        login: $order->contact,
                        password: $isNewUser ? $plainPassword : null,
                        title: (string) $order->title,
                    ));
                } catch (\Exception $e) {
                    Log::error('AccountCredentialsMail failed: '.$e->getMessage());
                }
            }

            // Уведомление админам об оплате (идемпотентно — этот код выполняется один раз).
            try {
                app(TelegramNotificationService::class)->notifyAdminsPayment([
                    'amount' => $order->amount,
                    'songs_count' => 1,
                    'contact' => $order->contact,
                    'context' => 'public_landing',
                    'user_id' => $user->user_id,
                    'payment_id' => $order->payment_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Admin payment notification (guest) failed: '.$e->getMessage());
            }

            return [
                'success' => true,
                'user_id' => $user->user_id,
                'is_new_user' => $isNewUser,
                'plain_password' => $plainPassword,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GuestOrderService handlePaymentSucceeded error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Находит пользователя по контакту или создаёт нового.
     * Возвращает [User, bool isNewUser, ?string plainPassword]
     */
    private function findOrCreateUser(GuestOrder $order): array
    {
        // 0. Если заказ уже привязан к юзеру (залогиненный при создании) — используем его
        if ($order->user_id) {
            $user = User::where('user_id', $order->user_id)->first();
            if ($user) {
                return [$user, false, null];
            }
        }

        // 1. Если тип email — ищем по email
        if ($order->contact_type === 'email') {
            $user = User::where('email', $order->contact)->first();
            if ($user) {
                return [$user, false, null];
            }
        }

        // 2. Для всех (email и phone) — ищем по contact
        $user = User::where('contact', $order->contact)->first();
        if ($user) {
            // Если у юзера не заполнен email и у нас email — дозаполним
            if ($order->contact_type === 'email' && empty($user->email)) {
                $user->update(['email' => $order->contact]);
            }

            return [$user, false, null];
        }

        // 3. Создаём нового
        $plainPassword = $this->generateReadablePassword();
        $user = $this->createUser($order, $plainPassword);

        return [$user, true, $plainPassword];
    }

    /**
     * Создаёт нового юзера (универсально для email и phone)
     */
    private function createUser(GuestOrder $order, string $plainPassword): User
    {
        $userId = $this->generateEmailUserId();

        $data = [
            'user_id' => $userId,
            'first_name' => $order->first_name ?: 'Гость',
            'contact' => $order->contact,
            'password' => bcrypt($plainPassword),
            'balance' => 0, // увеличим через increment
            'is_blocked' => false,
            'last_activity' => now(),
            'utm_source' => $order->utm_source ?: 'public_landing',
            'utm_medium' => $order->utm_medium,
            'utm_campaign' => $order->utm_campaign,
            'utm_content' => $order->utm_content,
            'utm_term' => $order->utm_term,
            'ym_client_id' => $order->ym_client_id,
        ];

        // Email заполняем только если контакт — email (чтобы не ломать уникальность)
        if ($order->contact_type === 'email') {
            $data['email'] = $order->contact;
        }

        return User::create($data);
    }

    /**
     * Генерирует уникальный user_id в диапазоне 9_000_000_000+
     * (чтобы не пересекаться с Telegram ID)
     */
    private function generateEmailUserId(): int
    {
        for ($i = 0; $i < 20; $i++) {
            $id = random_int(9_000_000_000, 9_999_999_999);
            if (! User::where('user_id', $id)->exists()) {
                return $id;
            }
        }
        throw new \RuntimeException('Cannot generate unique user_id');
    }

    /**
     * Генерирует читаемый пароль: слово + 4 цифры
     */
    private function generateReadablePassword(): string
    {
        $words = [
            'music', 'melody', 'song', 'beat', 'rhythm', 'harmony',
            'track', 'audio', 'sound', 'tune', 'chord', 'lyric',
        ];
        $word = $words[array_rand($words)];
        $digits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return ucfirst($word).$digits;
    }

    /**
     * Создаёт запись в payments (под существующую схему из ЛК)
     */
    private function recordPayment(GuestOrder $order, User $user): void
    {
        Payment::create([
            'user_id' => $user->user_id,
            'payment_id' => $order->payment_id,
            'songs_count' => 1,
            'amount' => (string) $order->amount,
            'status' => 'succeeded',
            'context' => 'public_landing',
        ]);
    }

    /**
     * Запускает генерацию песни для оплаченного гостевого заказа.
     * Идемпотентна — повторные вызовы безопасны.
     */
    public function startGeneration(GuestOrder $order, SunoService $sunoService): array
    {
        // Проверки
        if (! $order->isPaid() || ! $order->user_id) {
            return ['success' => false, 'error' => 'Заказ не оплачен'];
        }

        // Если генерация уже запущена / завершена — ничего не делаем
        if (in_array($order->status, ['generating', 'completed']) && $order->song_id) {
            $song = Song::find($order->song_id);

            return [
                'success' => true,
                'song_id' => $order->song_id,
                'already_started' => true,
                'is_ready' => $song ? (! empty($song->file_path) && ! empty($song->file_path_2)) : false,
            ];
        }

        try {
            $user = User::where('user_id', $order->user_id)->first();
            if (! $user) {
                throw new \RuntimeException('User not found for paid order');
            }

            // Списываем 1 песню с баланса (начислили в handlePaymentSucceeded)
            if ($user->balance < 1) {
                throw new \RuntimeException('Insufficient balance');
            }
            $user->decrement('balance', 1);

            // Готовим gender
            $vocalGender = in_array($order->vocal_gender, ['m', 'f', 'duet'])
                ? $order->vocal_gender
                : null;

            // Создаём Song (под реальную схему)
            $song = Song::create([
                'user_id' => $user->user_id,
                'title' => $order->title,
                'occasion' => $order->occasion,
                'genre' => $order->genre,
                'description' => $order->description,
                'lyrics' => $order->lyrics,
                'is_deleted' => 0,
                'plays_count' => 0,
            ]);

            // Запускаем Suno (формат ответа см. SunoService::generateMusic)
            $sunoParams = [
                'title' => $order->title,
                'lyrics' => $order->lyrics,
                'genre' => $order->genre,
                'artist' => $order->artist,
                'gender' => $vocalGender,
            ];

            // «Свой голос» (разовый, гостевой): kie voice_id выбран в визарде
            if (! empty($order->voice_id)) {
                $sunoParams['persona_id'] = $order->voice_id;
                $sunoParams['persona_source'] = 'kie';
            }

            $result = $sunoService->generateMusic($sunoParams);

            if (! $result['success']) {
                // Возвращаем песню на баланс + помечаем заказ как failed
                $user->increment('balance', 1);
                $song->update(['is_deleted' => 1, 'refunded_at' => now()]);
                $order->update(['status' => 'failed']);

                Log::error('Guest order Suno generation failed to start', [
                    'order_id' => $order->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);

                return ['success' => false, 'error' => $result['error'] ?? 'Ошибка запуска генерации'];
            }

            // Успех: сохраняем task_id в песне
            $taskId = $result['task_id'];
            $song->update([
                'suno_task_id' => $taskId,
            ]);

            $order->update([
                'status' => 'generating',
                'song_id' => $song->id,
                'suno_task_id' => $taskId,
            ]);

            // Диспатчим job — конструктор принимает (songId, taskId, userId)
            CheckSongGenerationStatus::dispatch($song->id, $taskId, $user->user_id)
                ->delay(now()->addSeconds(30));

            Log::info('Guest order generation started', [
                'order_id' => $order->id,
                'song_id' => $song->id,
                'task_id' => $taskId,
                'user_id' => $user->user_id,
            ]);

            return [
                'success' => true,
                'song_id' => $song->id,
                'task_id' => $taskId,
            ];

        } catch (\Exception $e) {
            Log::error('GuestOrderService startGeneration error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Повторный запуск генерации для заказа, у которого генерация упала.
     * Баланс к этому моменту уже возвращён (в startGeneration / job).
     */
    public function retryGeneration(GuestOrder $order, SunoService $sunoService): array
    {
        // Заказ со статусом 'failed' уже НЕ проходит isPaid(), поэтому проверяем факт оплаты по paid_at
        if (! $order->user_id || ! $order->paid_at) {
            return ['success' => false, 'error' => 'Заказ не оплачен'];
        }

        if ($order->status !== 'failed') {
            // Уже в работе или завершён — повторять нечего, отдаём текущее состояние
            return $this->startGeneration($order, $sunoService);
        }

        // Сбрасываем заказ в оплаченное состояние для нового прогона
        $order->update([
            'status' => 'paid',
            'song_id' => null,
            'suno_task_id' => null,
        ]);

        Log::info('Guest order generation retry requested', [
            'order_id' => $order->id,
            'token' => $order->token,
        ]);

        return $this->startGeneration($order->fresh(), $sunoService);
    }
}
