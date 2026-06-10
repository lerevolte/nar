<?php

namespace App\Http\Controllers;

use App\Models\GuestOrder;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\UsedPromoCode;
use App\Services\GuestOrderService;
use App\Services\SunoService;
use App\Services\YooKassaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Страница покупки песен
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $packages = YooKassaService::getPackages();

        return view('payment.index', compact('packages', 'user'));
    }

    /**
     * Создание платежа
     */
    public function create(Request $request, YooKassaService $yooKassa)
    {
        $request->validate([
            'songs_count' => 'required|integer|in:2,7,30',
            'contact' => 'nullable|string|max:255',
        ]);

        $user = $request->get('auth_user');
        $songsCount = (int) $request->input('songs_count');
        $contact = $request->input('contact', $user->contact);

        $result = $yooKassa->createPayment($user->user_id, $songsCount, $contact);

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json([
            'success' => true,
            'payment_url' => $result['payment_url'],
        ]);
    }

    /**
     * Страница после оплаты
     */
    public function success(Request $request, YooKassaService $yooKassa)
    {
        $user = $request->get('auth_user');
        $processed = false;
        $songsAdded = 0;

        if ($user) {
            $payment = Payment::where('user_id', $user->user_id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($payment) {
                $processed = $yooKassa->processSuccessfulPayment($payment->payment_id);
                if ($processed) {
                    $songsAdded = $payment->songs_count;
                }
            }
        }

        return view('payment.success', compact('processed', 'songsAdded'));
    }

    /**
     * Webhook от ЮKassa
     */
    public function webhook(Request $request, YooKassaService $yooKassa, GuestOrderService $guestOrderService, SunoService $sunoService)
    {
        $data = $request->all();

        Log::info('YooKassa webhook received', $data);

        if (! isset($data['event']) || ! isset($data['object']['id'])) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        $event = $data['event'];
        $paymentId = $data['object']['id'];

        // Интересует только успешная оплата; остальные события подтверждаем 200, чтобы ЮKassa не повторяла
        if ($event !== 'payment.succeeded') {
            return response()->json(['success' => true]);
        }

        // 1. Покупка пакета из ЛК (есть запись Payment по payment_id)
        if (Payment::where('payment_id', $paymentId)->exists()) {
            $ok = $yooKassa->processSuccessfulPayment($paymentId);

            // Не начислили (платёж ещё не succeeded / временный сбой) → 500, чтобы ЮKassa повторила
            return response()->json(['success' => $ok], $ok ? 200 : 500);
        }

        // 2. Гостевой заказ с лендинга (в metadata.order_token)
        $orderToken = $data['object']['metadata']['order_token'] ?? null;

        if ($orderToken) {
            return $this->handleGuestOrderWebhook($orderToken, $paymentId, $yooKassa, $guestOrderService, $sunoService);
        }

        // Неизвестный платёж — подтверждаем, повторять нечего
        Log::warning('YooKassa webhook: unknown payment', ['payment_id' => $paymentId]);

        return response()->json(['success' => true]);
    }

    /**
     * Обработка вебхука для гостевого заказа с лендинга /create-song.
     * Начисляет баланс и сразу запускает генерацию (не зависит от браузера пользователя).
     */
    private function handleGuestOrderWebhook(
        string $orderToken,
        string $paymentId,
        YooKassaService $yooKassa,
        GuestOrderService $guestOrderService,
        SunoService $sunoService
    ) {
        $order = GuestOrder::where('token', $orderToken)->first();

        if (! $order) {
            Log::warning('Webhook: guest order not found', ['token' => $orderToken]);

            return response()->json(['success' => true]); // повторять нечего
        }

        // Проверяем статус платежа через API ЮKassa (защита от подделки вебхука)
        $check = $yooKassa->checkPayment($paymentId);
        if (($check['status'] ?? null) !== 'succeeded') {
            Log::info('Webhook: guest payment not succeeded yet', [
                'token' => $orderToken,
                'status' => $check['status'] ?? 'unknown',
            ]);

            return response()->json(['success' => false], 500); // пусть ЮKassa повторит
        }

        $result = $guestOrderService->handlePaymentSucceeded($order);

        if (! ($result['success'] ?? false)) {
            Log::error('Webhook: guest order handlePaymentSucceeded failed', ['token' => $orderToken]);

            return response()->json(['success' => false], 500);
        }

        // Авто-старт генерации (идемпотентно — повторный вызов из поллинга безопасен)
        // Письмо с доступами и уведомление админам отправляются внутри handlePaymentSucceeded.
        try {
            $guestOrderService->startGeneration($order->fresh(), $sunoService);
        } catch (\Exception $e) {
            Log::error('Webhook: guest startGeneration failed: '.$e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    /**
     * API: Проверка статуса платежа
     */
    public function checkStatus(Request $request, YooKassaService $yooKassa)
    {
        $request->validate([
            'payment_id' => 'required|string',
        ]);

        $paymentId = $request->input('payment_id');
        $result = $yooKassa->checkPayment($paymentId);

        if ($result['status'] === 'succeeded') {
            $yooKassa->processSuccessfulPayment($paymentId);
        }

        return response()->json($result);
    }

    /**
     * API: Проверка промокода
     */
    public function checkPromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $user = $request->get('auth_user');
        $code = trim($request->input('code'));

        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            return response()->json(['error' => 'Промокод не найден'], 404);
        }

        if (! $promo->is_active) {
            return response()->json(['error' => 'Промокод неактивен'], 400);
        }

        if ($promo->current_uses >= $promo->max_uses) {
            return response()->json(['error' => 'Промокод исчерпан'], 400);
        }

        if ($promo->isUsedByUser($user->user_id)) {
            return response()->json(['error' => 'Вы уже использовали этот промокод'], 400);
        }

        return response()->json([
            'success' => true,
            'promo' => [
                'id' => $promo->id,
                'code' => $promo->code,
                'type' => $promo->type,
                'value' => $promo->value,             // цена в рублях (0 = бесплатно)
                'songs_amount' => $promo->songs_amount, // кол-во песен
                'songs_count' => $promo->songs_count,   // альтернативное поле
            ],
        ]);
    }

    /**
     * API: Применение промокода (бесплатная активация)
     */
    public function applyPromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $user = $request->get('auth_user');
        $code = trim($request->input('code'));

        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            return response()->json(['error' => 'Промокод не найден'], 404);
        }

        if (! $promo->is_active) {
            return response()->json(['error' => 'Промокод неактивен'], 400);
        }

        if ($promo->current_uses >= $promo->max_uses) {
            return response()->json(['error' => 'Промокод исчерпан'], 400);
        }

        if ($promo->isUsedByUser($user->user_id)) {
            return response()->json(['error' => 'Вы уже использовали этот промокод'], 400);
        }

        $songsToAdd = $promo->songs_amount ?: $promo->songs_count ?: 0;

        if ($songsToAdd <= 0) {
            return response()->json(['error' => 'Промокод не содержит бонусов'], 400);
        }

        // Если промокод бесплатный (value = 0) — сразу начисляем
        if ($promo->value <= 0) {
            DB::transaction(function () use ($promo, $user, $songsToAdd) {
                // Начисляем песни
                $user->increment('balance', $songsToAdd);

                // Записываем использование
                UsedPromoCode::create([
                    'user_id' => $user->user_id,
                    'promo_code_id' => $promo->id,
                    'used_at' => now(),
                ]);

                // Увеличиваем счётчик использований
                $promo->increment('current_uses');
            });

            Log::info('Promo code applied', [
                'code' => $promo->code,
                'user_id' => $user->user_id,
                'songs_added' => $songsToAdd,
                'new_balance' => $user->fresh()->balance,
            ]);

            return response()->json([
                'success' => true,
                'songs_added' => $songsToAdd,
                'new_balance' => $user->fresh()->balance,
                'message' => "+{$songsToAdd} песен добавлено на баланс!",
            ]);
        }

        // Если промокод платный (value > 0) — создаём платёж со скидкой
        return response()->json([
            'success' => true,
            'needs_payment' => true,
            'price' => $promo->value,
            'songs_amount' => $songsToAdd,
            'promo_id' => $promo->id,
            'message' => "{$songsToAdd} песен за {$promo->value} ₽",
        ]);
    }

    /**
     * API: Создание платежа по промокоду (со скидкой)
     */
    public function createPromoPayment(Request $request, YooKassaService $yooKassa)
    {
        $request->validate([
            'promo_id' => 'required|integer',
            'contact' => 'nullable|string|max:255',
        ]);

        $user = $request->get('auth_user');
        $promo = PromoCode::find($request->input('promo_id'));

        if (! $promo || ! $promo->isValid() || $promo->isUsedByUser($user->user_id)) {
            return response()->json(['error' => 'Промокод недействителен'], 400);
        }

        $songsToAdd = $promo->songs_amount ?: $promo->songs_count ?: 0;
        $price = $promo->value;
        $contact = $request->input('contact', $user->contact);

        if ($price <= 0 || $songsToAdd <= 0) {
            return response()->json(['error' => 'Некорректный промокод'], 400);
        }

        // Подготовка данных клиента
        $customerData = $this->prepareCustomerData($contact);
        if (isset($customerData['error'])) {
            return response()->json(['error' => $customerData['error']], 400);
        }

        try {
            $shopId = config('yookassa.shop_id', '');
            $secretKey = config('yookassa.secret_key', '');
            $returnUrl = config('yookassa.return_url', '');
            $apiUrl = 'https://api.yookassa.ru/v3';

            $idempotenceKey = \Illuminate\Support\Str::uuid()->toString();

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($shopId, $secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $idempotenceKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->post("{$apiUrl}/payments", [
                    'amount' => [
                        'value' => number_format($price, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => $returnUrl,
                    ],
                    'capture' => true,
                    'description' => "Промо: {$songsToAdd} песен ({$promo->code})",
                    'metadata' => [
                        'user_id' => (string) $user->user_id,
                        'songs_count' => (string) $songsToAdd,
                        'promo_code_id' => (string) $promo->id,
                    ],
                    'receipt' => [
                        'customer' => $customerData,
                        'items' => [[
                            'description' => "Генерация {$songsToAdd} песен (промо)",
                            'quantity' => '1.00',
                            'amount' => [
                                'value' => number_format($price, 2, '.', ''),
                                'currency' => 'RUB',
                            ],
                            'vat_code' => 1,
                            'payment_mode' => 'full_payment',
                            'payment_subject' => 'service',
                        ]],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('YooKassa promo payment error', ['body' => $response->body()]);

                return response()->json(['error' => 'Ошибка создания платежа'], 400);
            }

            $data = $response->json();

            // Сохраняем платёж
            Payment::create([
                'user_id' => $user->user_id,
                'payment_id' => $data['id'],
                'songs_count' => $songsToAdd,
                'amount' => (string) $price,
                'status' => 'pending',
                'context' => 'web',
            ]);

            // Записываем использование промокода
            DB::transaction(function () use ($promo, $user) {
                UsedPromoCode::create([
                    'user_id' => $user->user_id,
                    'promo_code_id' => $promo->id,
                    'used_at' => now(),
                ]);
                $promo->increment('current_uses');
            });

            Log::info('Promo payment created', [
                'user_id' => $user->user_id,
                'promo' => $promo->code,
                'payment_id' => $data['id'],
                'amount' => $price,
                'songs' => $songsToAdd,
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $data['confirmation']['confirmation_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('Promo payment exception: '.$e->getMessage());

            return response()->json(['error' => 'Ошибка платёжной системы'], 500);
        }
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

        if (str_contains($contact, '@') && filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $contact];
        }

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
