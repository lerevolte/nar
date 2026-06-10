<?php

namespace App\Http\Controllers;

use App\Models\ChartEntry;
use App\Models\ChartVote;
use App\Models\GuestOrder;
use App\Models\Song;
use App\Services\GuestOrderService;
use App\Services\LyricsGeneratorService;
use App\Services\SunoService;
use App\Services\TelegramAuthService;
use App\Services\VoiceService;
use App\Services\YooKassaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PublicGenerateController extends Controller
{
    /**
     * Публичная посадочная страница генерации песни
     */
    public function index(Request $request)
    {
        $occasions = [
            'birthday' => '🎂 День рождения',
            'new_year' => '🎄 Новый год',
            'holiday' => '🎉 Праздник / Юбилей',
            'confession' => '💕 Признание в любви',
            'wedding' => '💒 Свадьба',
            'anniversary' => '💑 Годовщина',
            'prank' => '😂 Розыгрыш',
            'support' => '🤗 Поддержка',
            'mothers_day' => '💐 День Матери',
            'graduation' => '🎓 Выпускной',
            'friendship' => '🤝 Для друга',
            'child' => '👶 Для ребёнка',
            'pet' => '🐾 Про питомца',
            'corporate' => '🏢 Корпоратив',
            'custom' => '✨ Свой вариант',
        ];

        $genres = [
            'pop' => '🎶 Поп',
            'rap' => '🎤 Рэп / Хип-хоп',
            'rock' => '🎸 Рок',
            'disco' => '🕺 Диско / Ретро',
            'chanson' => '🎻 Шансон',
            'rnb' => '🎷 R&B / Соул',
            'electro' => '⚡ Электронная',
            'indie' => '🌿 Инди',
            'folk' => '🪕 Фолк',
            'romantic' => '🌹 Баллада',
            'kids' => '🧸 Детская',
            'custom' => '✨ Свой вариант',
        ];

        $genreArtists = [
            'pop' => ['Zivert', 'Егор Крид', 'Дуа Липа', 'Тейлор Свифт', 'The Weeknd', 'Ариана Гранде', 'Эд Ширан'],
            'rap' => ['Баста', 'Oxxxymiron', 'Miyagi', 'Моргенштерн', 'Eminem', 'Drake', 'Kendrick Lamar'],
            'rock' => ['Кино', 'ДДТ', 'Сплин', 'Би-2', 'Queen', 'Linkin Park', 'Coldplay', 'Imagine Dragons'],
            'disco' => ['ABBA', 'Boney M', 'Руки Вверх', 'Bee Gees', 'Donna Summer'],
            'chanson' => ['Михаил Круг', 'Григорий Лепс', 'Любэ', 'Стас Михайлов', 'Ваенга'],
            'rnb' => ['Jony', 'Rauf & Faik', 'HammAli & Navai', 'Beyoncé', 'Bruno Mars', 'The Weeknd'],
            'electro' => ['Little Big', 'David Guetta', 'Calvin Harris', 'Avicii', 'Daft Punk'],
            'indie' => ['Монеточка', 'Макс Корж', 'Земфира', 'Billie Eilish', 'Lana Del Rey', 'Arctic Monkeys'],
            'folk' => ['Пелагея', 'Мельница', 'Ed Sheeran', 'The Lumineers', 'Hozier'],
            'romantic' => ['Эд Ширан', 'Adele', 'Jony', 'Полина Гагарина', 'Sam Smith', 'Lana Del Rey'],
            'kids' => ['Барбарики', 'Фиксики', 'Маша и Медведь', 'Смешарики'],
        ];

        $languages = LyricsGeneratorService::getLanguages();
        $price = 199;

        $authUser = $request->attributes->get('auth_user');

        // Топ треки за всё время (суммируем голоса по всем чартам)
        $entries = ChartEntry::select(
            'song_id',
            'user_id',
            \DB::raw('SUM(votes_count) as total_votes'),
            \DB::raw('MIN(id) as id'),
            \DB::raw('MIN(created_at) as first_added')
        )
            ->with(['song', 'user'])
            ->groupBy('song_id', 'user_id')
            ->having('total_votes', '>', 0)
            ->orderByDesc('total_votes')
            ->orderBy('first_added')
            ->take(10)
            ->get();

        $topTracks = $entries->map(function ($entry, $index) {
            $song = $entry->song;
            if (! $song || ! $song->file_path) {
                return null;
            }

            return [
                'position' => $index + 1,
                'song_id' => $entry->song_id,
                'title' => $song->title ?? 'Без названия',
                'author' => $entry->user->first_name ?? $entry->user->username ?? 'Автор',
                'votes' => (int) $entry->total_votes,
                'plays' => $song->plays_count ?? 0,
                'audio_url' => $song->file_path,
                'cover_url' => $song->cover_url,
                'genre' => $song->genre,
                'occasion' => $song->occasion,
                'lyrics' => $song->lyrics,
                'created_at' => $song->created_at ? $song->created_at->format('d.m.Y') : null,
                'user_id' => $entry->user_id,
            ];
        })->filter()->values()->toArray();

        $votedSongIds = [];
        if ($authUser) {
            $songIds = collect($topTracks)->pluck('song_id')->toArray();
            $entryIds = ChartEntry::whereIn('song_id', $songIds)->pluck('id')->toArray();
            $votedEntryIds = ChartVote::where('user_id', $authUser->user_id)
                ->whereIn('chart_entry_id', $entryIds)
                ->pluck('chart_entry_id')
                ->toArray();

            // Конвертируем entry_id -> song_id
            $votedSongIds = ChartEntry::whereIn('id', $votedEntryIds)
                ->pluck('song_id')
                ->unique()
                ->values()
                ->toArray();
        }

        return view('public.generate.index', compact(
            'occasions',
            'genres',
            'genreArtists',
            'languages',
            'price',
            'authUser',
            'topTracks',
            'votedSongIds'
        ));
    }

    /**
     * API: Генерация текста песни (публичная — с rate-limit)
     */
    public function generateLyrics(Request $request, LyricsGeneratorService $lyricsService)
    {
        // Rate limit: 10 запросов в час с одного IP
        $key = 'public-lyrics:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Слишком много запросов. Попробуй через '.ceil($seconds / 60).' мин.',
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'occasion' => 'required|string|max:500',
            'genre' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'language' => 'required|string|in:ru,en,de,es,fr,it',
            'artist' => 'nullable|string|max:255',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
        ]);

        $result = $lyricsService->generate([
            'occasion' => $request->input('occasion'),
            'genre' => $request->input('genre'),
            'description' => $request->input('description'),
            'language' => $request->input('language'),
            'artist' => $request->input('artist'),
            'vocal_gender' => $request->input('vocal_gender'),
        ]);

        if (! $result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка генерации'], 500);
        }

        $displayLyrics = LyricsGeneratorService::prepareLyricsForUser($result['lyrics']);

        return response()->json([
            'success' => true,
            'title' => $result['title'],
            'lyrics' => $result['lyrics'],
            'display_lyrics' => $displayLyrics,
            'comment' => $result['comment'] ?? '',
        ]);
    }

    /**
     * API: Перевод текста (публичный)
     */
    public function translateLyrics(Request $request, LyricsGeneratorService $lyricsService)
    {
        $key = 'public-translate:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json(['error' => 'Слишком много запросов'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'lyrics' => 'required|string|max:10000',
            'target_language' => 'required|string|in:ru,en,de,es,fr,it',
        ]);

        $result = $lyricsService->translate(
            $request->input('lyrics'),
            $request->input('target_language')
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка перевода'], 500);
        }

        return response()->json([
            'success' => true,
            'lyrics' => $result['lyrics'],
        ]);
    }

    /**
     * API: Улучшение текста (публичное)
     */
    public function improveLyrics(Request $request, LyricsGeneratorService $lyricsService)
    {
        $key = 'public-improve:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['error' => 'Слишком много запросов'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'lyrics' => 'required|string|max:10000',
            'feedback' => 'required|string|max:1000',
            'genre' => 'nullable|string|max:255',
            'artist' => 'nullable|string|max:255',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
        ]);

        $result = $lyricsService->improve(
            $request->input('lyrics'),
            $request->input('feedback'),
            [
                'occasion' => '',
                'genre' => $request->input('genre', ''),
                'artist' => $request->input('artist'),
                'vocal_gender' => $request->input('vocal_gender'),
            ]
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка'], 500);
        }

        $displayLyrics = LyricsGeneratorService::prepareLyricsForUser($result['lyrics']);

        return response()->json([
            'success' => true,
            'title' => $result['title'],
            'lyrics' => $result['lyrics'],
            'display_lyrics' => $displayLyrics,
            'comment' => $result['comment'] ?? '',
        ]);
    }

    /**
     * API: Создать заказ + платёж ЮKassa (гость)
     */
    public function createOrder(
        Request $request,
        LyricsGeneratorService $lyricsService,
        YooKassaService $yooKassa
    ) {
        // Rate limit: 5 заказов в час с IP
        $key = 'public-order:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Слишком много заказов. Попробуй через час.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            // Данные песни
            'title' => 'nullable|string|max:255',
            'lyrics' => 'required|string|max:10000',
            'genre' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
            'voice_id' => 'nullable|string|max:255',
            'language' => 'required|string|in:ru,en,de,es,fr,it',
            'occasion' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:10000',
            // Контакт
            'first_name' => 'nullable|string|max:100',
            'contact' => 'required|string|max:255',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
            'utm_term' => 'nullable|string|max:100',
            'ym_client_id' => 'nullable|string|max:50',
        ]);

        // Название: если пусто или generic — сгенерируем из текста
        $title = $lyricsService->ensureTitle(
            $request->input('title'),
            $request->input('lyrics')
        );

        // Парсим контакт (email или телефон)
        $contactRaw = trim($request->input('contact'));
        $contactData = $this->parseContact($contactRaw);

        if (isset($contactData['error'])) {
            return response()->json(['error' => $contactData['error']], 422);
        }

        // Цена
        $price = 199;

        $authUser = $request->attributes->get('auth_user');

        // Создаём заказ
        $order = GuestOrder::create([
            'token' => Str::random(48),
            'contact' => $contactData['value'],
            'contact_type' => $contactData['type'],
            'first_name' => $request->input('first_name') ?: null,
            'title' => $title,
            'lyrics' => $request->input('lyrics'),
            'genre' => $request->input('genre'),
            'artist' => $request->input('artist'),
            'vocal_gender' => $request->input('vocal_gender', 'random'),
            'voice_id' => $request->input('voice_id'),
            'language' => $request->input('language', 'ru'),
            'occasion' => $request->input('occasion'),
            'description' => $request->input('description'),
            'amount' => $price,
            'status' => 'pending_payment',
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_content' => $request->input('utm_content'),
            'utm_term' => $request->input('utm_term'),
            'ym_client_id' => $request->input('ym_client_id'),
            'user_id' => $authUser?->user_id,
        ]);

        // Создаём платёж в ЮKassa
        $paymentResult = $this->createYooKassaPayment($order);

        if (! $paymentResult['success']) {
            $order->update(['status' => 'failed']);

            return response()->json([
                'error' => $paymentResult['error'] ?? 'Ошибка создания платежа',
            ], 500);
        }

        $order->update(['payment_id' => $paymentResult['payment_id']]);

        Log::info('Guest order created', [
            'order_id' => $order->id,
            'token' => $order->token,
            'contact' => $order->contact,
            'amount' => $order->amount,
        ]);

        return response()->json([
            'success' => true,
            'order_token' => $order->token,
            'payment_url' => $paymentResult['payment_url'],
        ]);
    }

    /**
     * Страница после возврата с ЮKassa (заглушка — реальная обработка в Шаге 5)
     */
    public function success(Request $request)
    {
        $token = $request->query('order');

        if (! $token) {
            return redirect()->route('public.generate');
        }

        $order = GuestOrder::where('token', $token)->first();

        if (! $order) {
            return redirect()->route('public.generate')->with('error', 'Заказ не найден');
        }

        $authUser = $request->attributes->get('auth_user');

        return view('public.generate.success', compact('order', 'authUser'));
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Парсит контакт: email или телефон
     */
    private function parseContact(string $contact): array
    {
        // Email
        if (str_contains($contact, '@')) {
            if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                return ['type' => 'email', 'value' => mb_strtolower($contact)];
            }

            return ['error' => 'Некорректный email'];
        }

        // Телефон
        $digits = preg_replace('/\D/', '', $contact);
        if (strlen($digits) === 11) {
            if (str_starts_with($digits, '8')) {
                $digits = '7'.substr($digits, 1);
            }
        } elseif (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        if (strlen($digits) !== 11 || ! str_starts_with($digits, '7')) {
            return ['error' => 'Введите корректный email или телефон'];
        }

        return ['type' => 'phone', 'value' => $digits];
    }

    /**
     * Создаёт платёж в ЮKassa напрямую (без привязки к user_id — для гостя)
     */
    private function createYooKassaPayment(GuestOrder $order): array
    {
        $shopId = config('yookassa.shop_id');
        $secretKey = config('yookassa.secret_key');

        if (! $shopId || ! $secretKey) {
            return ['success' => false, 'error' => 'ЮKassa не настроена'];
        }

        // Данные клиента для чека
        $customer = $order->contact_type === 'email'
            ? ['email' => $order->contact]
            : ['phone' => $order->contact];

        // URL возврата — передаём token в query
        $returnUrl = route('public.generate.success', ['order' => $order->token]);

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->withHeaders([
                    'Idempotence-Key' => Str::uuid()->toString(),
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->post('https://api.yookassa.ru/v3/payments', [
                    'amount' => [
                        'value' => number_format($order->amount, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => $returnUrl,
                    ],
                    'capture' => true,
                    'description' => "Генерация песни «{$order->title}»",
                    'metadata' => [
                        'order_token' => $order->token,
                        'source' => 'public_landing',
                    ],
                    'receipt' => [
                        'customer' => $customer,
                        'items' => [[
                            'description' => 'Генерация песни с помощью ИИ',
                            'quantity' => '1.00',
                            'amount' => [
                                'value' => number_format($order->amount, 2, '.', ''),
                                'currency' => 'RUB',
                            ],
                            'vat_code' => 1,
                            'payment_mode' => 'full_payment',
                            'payment_subject' => 'service',
                        ]],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('YooKassa guest payment error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'error' => 'Платёжная система недоступна. Попробуй позже.'];
            }

            $data = $response->json();

            return [
                'success' => true,
                'payment_id' => $data['id'],
                'payment_url' => $data['confirmation']['confirmation_url'],
            ];

        } catch (\Exception $e) {
            Log::error('YooKassa guest exception: '.$e->getMessage());

            return ['success' => false, 'error' => 'Ошибка платёжной системы'];
        }
    }

    /**
     * Webhook от ЮKassa — уведомление об оплате
     */
    public function yooKassaWebhook(Request $request, GuestOrderService $orderService)
    {
        $event = $request->input('event');
        $payment = $request->input('object');

        Log::info('YooKassa webhook received (public)', [
            'event' => $event,
            'payment_id' => $payment['id'] ?? null,
            'status' => $payment['status'] ?? null,
        ]);

        if ($event !== 'payment.succeeded') {
            return response()->json(['ok' => true]); // другие события игнорируем
        }

        $orderToken = $payment['metadata']['order_token'] ?? null;

        // Если это не наш гостевой заказ (например, оплата из ЛК) — пропускаем
        if (! $orderToken) {
            return response()->json(['ok' => true]);
        }

        $order = GuestOrder::where('token', $orderToken)->first();

        if (! $order) {
            Log::warning('Webhook: guest order not found', ['token' => $orderToken]);

            return response()->json(['ok' => true]); // всё равно 200, чтобы ЮKassa не ретраила
        }

        $orderService->handlePaymentSucceeded($order);

        return response()->json(['ok' => true]);
    }

    /**
     * API: проверка статуса заказа (polling с success-страницы).
     * При первом обнаружении paid — ставим session cookie (авто-логин).
     */
    public function checkOrderStatus(
        Request $request,
        GuestOrderService $orderService
    ) {
        $token = $request->query('token');

        if (! $token) {
            return response()->json(['error' => 'No token'], 400);
        }

        $order = GuestOrder::where('token', $token)->first();
        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $wasNotPaid = ! $order->isPaid();

        if ($order->status === 'pending_payment' && $order->payment_id) {
            $this->refreshPaymentFromYooKassa($order, $orderService);
            $order->refresh();
        }

        $payload = [
            'status' => $order->status,
            'is_paid' => $order->isPaid(),
            'contact' => $order->contact,
            'title' => $order->title,
            'amount' => (int) $order->amount,
        ];

        // Только что оплачено — выдаём одноразовый login_token (5 минут)
        // Только если у этого браузера ещё нет cookie tg_session
        if ($wasNotPaid && $order->isPaid() && $order->user_id && ! $request->cookie('tg_session')) {
            $loginToken = \Illuminate\Support\Str::random(64);
            $order->update([
                'login_token' => hash('sha256', $loginToken),
                'login_token_expires_at' => now()->addMinutes(5),
            ]);
            $payload['login_token'] = $loginToken;

            \Illuminate\Support\Facades\Log::info('Login token issued', [
                'order_id' => $order->id,
                'expires_in_min' => 5,
            ]);
        }

        return response()->json($payload);
    }

    /**
     * Спрашивает ЮKassa о статусе платежа и обрабатывает если succeeded
     * (fallback если webhook не пришёл)
     */
    private function refreshPaymentFromYooKassa(GuestOrder $order, GuestOrderService $orderService): void
    {
        $shopId = config('yookassa.shop_id');
        $secretKey = config('yookassa.secret_key');

        if (! $shopId || ! $secretKey || ! $order->payment_id) {
            return;
        }

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->timeout(10)
                ->get("https://api.yookassa.ru/v3/payments/{$order->payment_id}");

            if (! $response->successful()) {
                Log::warning('YooKassa status check failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                ]);

                return;
            }

            $data = $response->json();
            $paymentStatus = $data['status'] ?? null;

            if ($paymentStatus === 'succeeded') {
                $orderService->handlePaymentSucceeded($order);
            } elseif (in_array($paymentStatus, ['canceled', 'cancelled'])) {
                $order->update(['status' => 'cancelled']);
            }

        } catch (\Exception $e) {
            Log::error('YooKassa status check exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * API: Старт генерации после подтверждения оплаты
     */
    public function startGeneration(
        Request $request,
        GuestOrderService $orderService,
        SunoService $sunoService
    ) {
        $token = $request->query('token') ?: $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'No token'], 400);
        }

        $order = GuestOrder::where('token', $token)->first();

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if (! $order->isPaid()) {
            return response()->json(['error' => 'Order not paid'], 402);
        }

        $result = $orderService->startGeneration($order, $sunoService);

        return response()->json($result);
    }

    /**
     * API: Повторный запуск генерации после ошибки (баланс уже возвращён)
     */
    public function retryGeneration(
        Request $request,
        GuestOrderService $orderService,
        SunoService $sunoService
    ) {
        $token = $request->query('token') ?: $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'No token'], 400);
        }

        $order = GuestOrder::where('token', $token)->first();

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if (! $order->isPaid()) {
            return response()->json(['error' => 'Order not paid'], 402);
        }

        $result = $orderService->retryGeneration($order, $sunoService);

        return response()->json($result);
    }

    public function songStatus(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return response()->json(['error' => 'No token'], 400);
        }

        $order = GuestOrder::where('token', $token)->first();

        if (! $order || ! $order->song_id) {
            return response()->json(['status' => 'not_started']);
        }

        $song = Song::find($order->song_id);
        if (! $song) {
            return response()->json(['status' => 'not_started']);
        }

        // Готово = есть оба файла
        $isReady = ! empty($song->file_path) && ! empty($song->file_path_2);

        // Частичная готовность (один файл уже скачан — редкий случай)
        $isPartial = ! empty($song->file_path) || ! empty($song->file_path_2);

        if ($isReady && $order->status !== 'completed') {
            $order->update(['status' => 'completed']);
        }

        // Удалённый — считаем ошибкой
        if ($song->is_deleted) {
            return response()->json([
                'status' => 'failed',
                'song_id' => $song->id,
            ]);
        }

        return response()->json([
            'status' => $isReady ? 'completed' : ($isPartial ? 'partial' : 'generating'),
            'song_id' => $song->id,
            'title' => $song->title,
            'file_path' => $song->file_path,
            'file_path_2' => $song->file_path_2,
            'cover_url' => $song->cover_url,
        ]);
    }

    /**
     * API: получить login+password для показа пользователю (один раз).
     * После прочтения — удаляется из кеша.
     */
    public function getCredentials(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return response()->json(['error' => 'No token'], 400);
        }

        $order = GuestOrder::where('token', $token)->first();
        if (! $order || ! $order->isPaid()) {
            return response()->json(['has_credentials' => false]);
        }

        $cacheKey = "guest_credentials:{$order->token}";
        $creds = Cache::get($cacheKey);

        if (! $creds) {
            // Уже показано или юзер существовал до оплаты
            return response()->json([
                'has_credentials' => false,
                'login' => $order->contact,
                'is_new' => false,
            ]);
        }

        // Удаляем из кеша сразу после чтения
        Cache::forget($cacheKey);

        return response()->json([
            'has_credentials' => true,
            'login' => $creds['login'],
            'password' => $creds['password'],
            'is_new' => $creds['is_new'] ?? true,
        ]);
    }

    /**
     * Автологин гостя: ставит cookie tg_session для оплаченного заказа.
     * Возвращает {ok: true} всегда (безопасно — cookie ставится только если есть право).
     */
    public function autoLogin(
        Request $request,
        TelegramAuthService $authService
    ) {
        $orderToken = $request->input('order_token');
        $loginToken = $request->input('login_token');

        if (! $orderToken || ! $loginToken) {
            return response()->json(['ok' => false, 'reason' => 'no_token'], 400);
        }

        $order = GuestOrder::where('token', $orderToken)->first();
        if (! $order || ! $order->isPaid() || ! $order->user_id) {
            return response()->json(['ok' => false, 'reason' => 'not_paid'], 403);
        }

        // Проверка login_token: хеш совпадает + не истёк
        if (
            empty($order->login_token) ||
            ! hash_equals($order->login_token, hash('sha256', $loginToken)) ||
            ! $order->login_token_expires_at ||
            $order->login_token_expires_at->isPast()
        ) {
            return response()->json(['ok' => false, 'reason' => 'invalid_or_expired_token'], 403);
        }

        // Одноразово: сразу гасим токен
        $order->update([
            'login_token' => null,
            'login_token_expires_at' => null,
        ]);

        $user = \App\Models\User::where('user_id', $order->user_id)->first();
        if (! $user) {
            return response()->json(['ok' => false, 'reason' => 'no_user'], 404);
        }

        $session = $authService->createSession($user);

        $secure = $request->isSecure();
        $cookie = cookie(
            'tg_session',
            $session->session_token,
            60 * 24 * 7,
            '/',
            null,
            $secure,
            true,
            false,
            'Lax'
        );

        \Illuminate\Support\Facades\Log::info('Auto-login success', [
            'user_id' => $user->user_id,
            'order_token' => $order->token,
        ]);

        return response()->json([
            'ok' => true,
            'user_id' => $user->user_id,
        ])->withCookie($cookie);
    }

    /**
     * Залогиненный юзер с балансом: создаём оплаченный заказ и сразу запускаем генерацию
     */
    public function createFreeOrder(
        Request $request,
        LyricsGeneratorService $lyricsService,
        GuestOrderService $orderService,
        SunoService $sunoService
    ) {
        $authUser = $request->attributes->get('auth_user');

        if (! $authUser) {
            return response()->json(['error' => 'Требуется авторизация'], 401);
        }

        if ($authUser->balance < 1) {
            return response()->json(['error' => 'Недостаточно песен на балансе'], 402);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'lyrics' => 'required|string|max:10000',
            'genre' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
            'voice_id' => 'nullable|string|max:255',
            'language' => 'required|string|in:ru,en,de,es,fr,it',
            'occasion' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:10000',
        ]);

        $title = $lyricsService->ensureTitle(
            $request->input('title'),
            $request->input('lyrics')
        );

        // Создаём "заказ" сразу в статусе paid — для единого пайплайна
        $order = GuestOrder::create([
            'token' => \Illuminate\Support\Str::random(48),
            'contact' => $authUser->email ?? $authUser->contact ?? "user_{$authUser->user_id}",
            'contact_type' => filter_var($authUser->email ?? '', FILTER_VALIDATE_EMAIL) ? 'email' : 'phone',
            'first_name' => $authUser->first_name,
            'title' => $title,
            'lyrics' => $request->input('lyrics'),
            'genre' => $request->input('genre'),
            'artist' => $request->input('artist'),
            'vocal_gender' => $request->input('vocal_gender', 'random'),
            'voice_id' => $request->input('voice_id'),
            'language' => $request->input('language', 'ru'),
            'occasion' => $request->input('occasion'),
            'description' => $request->input('description'),
            'amount' => 0, // бесплатно — баланс
            'status' => 'paid',
            'user_id' => $authUser->user_id,
            'paid_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'utm_source' => 'balance',
        ]);

        // Запускаем генерацию немедленно (внутри списывается 1 с баланса)
        $result = $orderService->startGeneration($order, $sunoService);

        if (! $result['success']) {
            return response()->json([
                'error' => $result['error'] ?? 'Ошибка запуска генерации',
            ], 500);
        }

        \Illuminate\Support\Facades\Log::info('Free generation started (balance)', [
            'user_id' => $authUser->user_id,
            'order_id' => $order->id,
            'song_id' => $result['song_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'order_token' => $order->token,
            'redirect_url' => route('public.generate.success', ['order' => $order->token]),
        ]);
    }

    public function prepareUserLyrics(
        Request $request,
        LyricsGeneratorService $lyricsService
    ) {
        $request->headers->set('Accept', 'application/json');

        $key = 'public-prepare:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['error' => 'Слишком много запросов. Попробуй через час.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'lyrics' => 'required|string|max:20000',
            'title' => 'nullable|string|max:255',
        ]);

        $lyrics = trim($request->input('lyrics'));
        $title = $lyricsService->ensureTitle(
            $request->input('title'),
            $lyrics
        );

        return response()->json([
            'success' => true,
            'lyrics' => $lyrics,
            'title' => $title,
        ]);
    }

    // ==========================================
    // «СВОЙ ГОЛОС» (гостевой, разовый, без авторизации)
    // Стейтлесс: taskId/voiceId держит клиент, в БД пишем только итог в guest_orders.voice_id
    // ==========================================

    /**
     * Загрузка исходного / verify аудио гостем → публичный URL для Kie
     */
    public function voiceUpload(Request $request)
    {
        $key = 'public-voice-upload:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['error' => 'Слишком много загрузок. Попробуй позже.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'audio' => 'required|file|max:20480|mimes:mp3,wav,m4a,mp4,ogg,webm',
        ]);

        $file = $request->file('audio');
        $dir = public_path('uploads/voices');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = time().'-'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $file->move($dir, $name);

        $url = 'https://narepite.site/uploads/voices/'.$name;

        return response()->json(['success' => true, 'url' => $url]);
    }

    /**
     * Шаг 1: запрос verify-фразы по исходному аудио → Kie taskId
     */
    public function voiceCreate(Request $request, VoiceService $voiceService)
    {
        $key = 'public-voice-create:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'Слишком много попыток. Попробуй через час.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'source_audio_url' => 'required|string|url|max:500',
            'vocal_start' => 'required|integer|min:0',
            'vocal_end' => 'required|integer|min:1',
            'language' => 'nullable|string|max:5',
        ]);

        $result = $voiceService->requestVerifyPhrase(
            $request->input('source_audio_url'),
            $request->input('vocal_start'),
            $request->input('vocal_end'),
            $request->input('language', 'ru')
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка'], 400);
        }

        return response()->json(['success' => true, 'task_id' => $result['task_id']]);
    }

    /**
     * Поллинг: статус verify-фразы по taskId
     */
    public function voicePhrase(Request $request, VoiceService $voiceService)
    {
        $taskId = $request->query('task_id');
        if (! $taskId) {
            return response()->json(['error' => 'No task_id'], 400);
        }

        $result = $voiceService->getValidateInfo($taskId);

        if ($result['status'] === 'ready') {
            return response()->json(['status' => 'ready', 'verify_phrase' => $result['verify_phrase']]);
        }
        if ($result['status'] === 'failed') {
            return response()->json(['status' => 'failed', 'error' => $result['error'] ?? 'Ошибка']);
        }

        return response()->json(['status' => 'processing']);
    }

    /**
     * Шаг 2: отправка verify-аудио → запуск генерации голоса (Kie generate taskId)
     */
    public function voiceGenerate(Request $request, VoiceService $voiceService)
    {
        $key = 'public-voice-generate:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'Слишком много попыток. Попробуй через час.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $request->validate([
            'task_id' => 'required|string|max:255',
            'verify_audio_url' => 'required|string|url|max:500',
        ]);

        $result = $voiceService->generateVoice(
            $request->input('task_id'),
            $request->input('verify_audio_url'),
            'Мой голос'
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка'], 400);
        }

        return response()->json(['success' => true, 'task_id' => $result['task_id']]);
    }

    /**
     * Поллинг: статус генерации голоса по taskId → итоговый Kie voice_id
     */
    public function voiceStatus(Request $request, VoiceService $voiceService)
    {
        $taskId = $request->query('task_id');
        if (! $taskId) {
            return response()->json(['error' => 'No task_id'], 400);
        }

        $result = $voiceService->getRecordInfo($taskId);

        if ($result['status'] === 'ready' && ! empty($result['voice_id'])) {
            return response()->json(['status' => 'ready', 'voice_id' => $result['voice_id']]);
        }
        if ($result['status'] === 'failed') {
            return response()->json(['status' => 'failed', 'error' => $result['error'] ?? 'Ошибка']);
        }

        return response()->json(['status' => 'processing']);
    }
}
