<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YooKassaService
{
    private string $shopId;

    private string $secretKey;

    private string $returnUrl;

    private string $apiUrl = 'https://api.yookassa.ru/v3';

    public function __construct()
    {
        $this->shopId = config('yookassa.shop_id', '');
        $this->secretKey = config('yookassa.secret_key', '');
        $this->returnUrl = config('yookassa.return_url', '');
    }

    /**
     * Получить доступные пакеты
     */
    public static function getPackages(): array
    {
        return config('yookassa.packages', []);
    }

    /**
     * Создать платёж
     */
    public function createPayment(int $userId, int $songsCount, ?string $contact = null): array
    {
        if (! $this->shopId || ! $this->secretKey) {
            return ['success' => false, 'error' => 'ЮKassa не настроена'];
        }

        $packages = self::getPackages();

        if (! isset($packages[$songsCount])) {
            return ['success' => false, 'error' => 'Неверный пакет'];
        }

        $package = $packages[$songsCount];
        $price = $package['price'];

        // Подготовка данных клиента
        $customerData = $this->prepareCustomerData($contact);

        if (isset($customerData['error'])) {
            return ['success' => false, 'error' => $customerData['error']];
        }

        try {
            $idempotenceKey = Str::uuid()->toString();

            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $idempotenceKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->post("{$this->apiUrl}/payments", [
                    'amount' => [
                        'value' => number_format($price, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => $this->returnUrl,
                    ],
                    'capture' => true,
                    'description' => "Покупка {$songsCount} песен",
                    'metadata' => [
                        'user_id' => (string) $userId,
                        'songs_count' => (string) $songsCount,
                    ],
                    'receipt' => [
                        'customer' => $customerData,
                        'items' => [
                            [
                                'description' => "Генерация {$songsCount} песен",
                                'quantity' => '1.00',
                                'amount' => [
                                    'value' => number_format($price, 2, '.', ''),
                                    'currency' => 'RUB',
                                ],
                                'vat_code' => 1,
                                'payment_mode' => 'full_payment',
                                'payment_subject' => 'service',
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('YooKassa create payment error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'error' => 'Ошибка создания платежа'];
            }

            $data = $response->json();

            // Сохраняем платёж в БД (используем существующую таблицу)
            Payment::create([
                'user_id' => $userId,
                'payment_id' => $data['id'],
                'songs_count' => $songsCount,
                'amount' => (string) $price,
                'status' => 'pending',
                'context' => 'web', // отличаем от бота
            ]);

            Log::info('Payment created', [
                'user_id' => $userId,
                'payment_id' => $data['id'],
                'amount' => $price,
                'context' => 'web',
            ]);

            return [
                'success' => true,
                'payment_url' => $data['confirmation']['confirmation_url'],
                'payment_id' => $data['id'],
            ];

        } catch (\Exception $e) {
            Log::error('YooKassa exception: '.$e->getMessage());

            return ['success' => false, 'error' => 'Ошибка платёжной системы'];
        }
    }

    /**
     * Проверить статус платежа
     */
    public function checkPayment(string $paymentId): array
    {
        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->timeout(10)
                ->get("{$this->apiUrl}/payments/{$paymentId}");

            if (! $response->successful()) {
                return ['status' => 'error', 'error' => 'Не удалось проверить платёж'];
            }

            $data = $response->json();

            return [
                'status' => $data['status'],
                'user_id' => (int) ($data['metadata']['user_id'] ?? 0),
                'songs_count' => (int) ($data['metadata']['songs_count'] ?? 0),
                'amount' => $data['amount']['value'],
            ];

        } catch (\Exception $e) {
            Log::error('YooKassa check error: '.$e->getMessage());

            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Обработать успешный платёж
     */
    public function processSuccessfulPayment(string $paymentId): bool
    {
        $payment = Payment::where('payment_id', $paymentId)->first();

        if (! $payment) {
            Log::warning('Payment not found', ['payment_id' => $paymentId]);

            return false;
        }

        if ($payment->status === 'succeeded') {
            Log::info('Payment already processed', ['payment_id' => $paymentId]);

            return true;
        }

        // Проверяем статус в ЮKassa (авторитетный источник — защита от подделки вебхука)
        $result = $this->checkPayment($paymentId);

        if ($result['status'] !== 'succeeded') {
            Log::info('Payment not succeeded yet', [
                'payment_id' => $paymentId,
                'status' => $result['status'],
            ]);

            return false;
        }

        // Начисляем песни атомарно, с блокировкой строки платежа (защита от гонок/дублей)
        $credited = false;

        try {
            DB::transaction(function () use ($paymentId, &$credited) {
                $payment = Payment::where('payment_id', $paymentId)->lockForUpdate()->first();

                // Повторная проверка под блокировкой — другой запрос мог успеть начислить
                if (! $payment || $payment->status === 'succeeded') {
                    return;
                }

                $user = User::where('user_id', $payment->user_id)->lockForUpdate()->first();

                if (! $user) {
                    throw new \RuntimeException('User not found for payment '.$paymentId);
                }

                $user->increment('balance', $payment->songs_count);
                $payment->update(['status' => 'succeeded']);
                $credited = true;
            });
        } catch (\Exception $e) {
            Log::error('processSuccessfulPayment transaction failed', [
                'payment_id' => $paymentId,
                'user_id' => $payment->user_id,
                'error' => $e->getMessage(),
            ]);

            return false; // вернём ошибку → ЮKassa повторит доставку
        }

        if ($credited) {
            $payment->refresh();
            $user = User::where('user_id', $payment->user_id)->first();

            Log::info('Payment processed successfully', [
                'payment_id' => $paymentId,
                'user_id' => $payment->user_id,
                'songs_added' => $payment->songs_count,
                'new_balance' => $user?->balance,
            ]);

            // Уведомление админам — не должно ломать начисление
            try {
                app(TelegramNotificationService::class)->notifyAdminsPayment([
                    'amount' => $payment->amount,
                    'songs_count' => $payment->songs_count,
                    'contact' => $user?->contact ?? $user?->email,
                    'context' => $payment->context,
                    'user_id' => $payment->user_id,
                    'payment_id' => $paymentId,
                ]);
            } catch (\Exception $e) {
                Log::error('Admin payment notification failed: '.$e->getMessage());
            }
        }

        return true;
    }

    /**
     * Подготовить данные клиента для чека
     */
    private function prepareCustomerData(?string $contact): array
    {
        if (! $contact) {
            return ['email' => 'support@narepite.com'];
        }

        $contact = trim($contact);

        // Email
        if (str_contains($contact, '@') && filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $contact];
        }

        // Телефон
        $phone = $this->normalizePhone($contact);
        if ($phone) {
            return ['phone' => $phone];
        }

        return ['error' => 'Некорректный Email или телефон'];
    }

    /**
     * Нормализовать телефон
     */
    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11) {
            if (str_starts_with($digits, '8')) {
                $digits = '7'.substr($digits, 1);
            }
        } elseif (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        if (strlen($digits) !== 11 || ! str_starts_with($digits, '7')) {
            return null;
        }

        return $digits;
    }
}
