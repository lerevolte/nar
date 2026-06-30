<?php

namespace App\Http\Controllers;

use App\Jobs\CheckSongGenerationStatus;
use App\Models\Song;
use App\Services\AudioUploadService;
use App\Services\SunoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Операции над треками поверх Suno API:
 *  - extend          — продлить существующий трек
 *  - upload-cover     — кавер на загруженный файл
 *  - upload-extend    — продлить загруженный файл
 *  - add-instrumental — убрать вокал (сделать минус)
 *  - add-vocals       — добавить вокал к минусу
 *  - mashup           — смешать два трека
 *  - replace-section  — заменить фрагмент (off-balance, отдельная оплата)
 *
 * Каждая операция (кроме replace-section) списывает 1 песню с баланса,
 * создаёт дочернюю Song и переиспользует CheckSongGenerationStatus.
 */
class TrackEditController extends Controller
{
    /** Операции, списывающие 1 песню с баланса. */
    private const BILLABLE_OPS = [
        'extend', 'upload_cover', 'upload_extend',
        'add_instrumental', 'add_vocals', 'mashup',
    ];

    public function __construct(private SunoService $suno) {}

    // ---------------------------------------------------------------
    //  Загрузка пользовательского аудио
    // ---------------------------------------------------------------

    public function upload(Request $request, AudioUploadService $uploads)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user)) {
            return $denied;
        }

        $key = 'track-ops-upload:'.$user->user_id;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['error' => 'Слишком много загрузок. Попробуй позже.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $maxKb = ((int) config('services.track_ops.upload_max_mb', 20)) * 1024;
        $request->validate([
            'audio' => "required|file|max:{$maxKb}|mimes:mp3,wav,m4a,mp4,ogg,webm,flac",
        ]);

        try {
            $url = $uploads->store($request->file('audio'), (int) $user->user_id);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'url' => $url]);
    }

    // ---------------------------------------------------------------
    //  Операции над существующими треками
    // ---------------------------------------------------------------

    public function extend(Request $request)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user, billable: true)) {
            return $denied;
        }

        $request->validate([
            'song_id' => 'required|integer',
            'variant' => 'nullable|integer|in:1,2',
            'prompt' => 'nullable|string|max:5000',
            'style' => 'nullable|string|max:1000',
            'title' => 'nullable|string|max:100',
            'continue_at' => 'nullable|numeric|min:0',
            'vocal_gender' => 'nullable|string|in:m,f',
        ]);

        $song = $this->ownedSong($user, (int) $request->input('song_id'));
        if (! $song) {
            return response()->json(['error' => 'Трек не найден'], 404);
        }

        $variant = (int) $request->input('variant', 1);
        $audioId = $variant === 2 ? $song->audio_id_2 : $song->audio_id_1;
        if (! $audioId) {
            return response()->json(['error' => 'У трека нет данных для продления'], 422);
        }

        $result = $this->suno->extendMusic([
            'audio_id' => $audioId,
            'api_source' => $song->api_source,
            'model' => $song->model ?: null,
            'prompt' => $request->input('prompt'),
            'style' => $request->input('style'),
            'title' => $request->input('title'),
            'continue_at' => $request->input('continue_at'),
            'vocal_gender' => $request->input('vocal_gender'),
        ]);

        return $this->launch($user, $result, 'extend', [
            'title' => $request->input('title') ?: ($song->title.' (продолжение)'),
            'genre' => $song->genre,
            'lyrics' => $song->lyrics,
            'parent_song_id' => $song->id,
            'source_audio_id' => $audioId,
            'api_source' => $song->api_source,
            'model' => $song->model,
        ]);
    }

    public function replaceSection(Request $request)
    {
        $user = $this->user($request);
        // Off-balance: отдельная оплата. Пока доступно только админам (обкатка),
        // списания баланса нет. Интеграция платежа — на этапе общего релиза.
        if ($denied = $this->gate($user)) {
            return $denied;
        }

        $request->validate([
            'song_id' => 'required|integer',
            'variant' => 'nullable|integer|in:1,2',
            'prompt' => 'required|string|max:5000',
            'tags' => 'required|string|max:1000',
            'title' => 'nullable|string|max:100',
            'full_lyrics' => 'required|string',
            'infill_start_s' => 'required|numeric|min:0',
            'infill_end_s' => 'required|numeric|gt:infill_start_s',
            'negative_tags' => 'nullable|string|max:1000',
        ]);

        $song = $this->ownedSong($user, (int) $request->input('song_id'));
        if (! $song) {
            return response()->json(['error' => 'Трек не найден'], 404);
        }

        if (! $song->suno_task_id) {
            return response()->json(['error' => 'Замена фрагмента доступна только для треков, сгенерированных у нас'], 422);
        }

        $variant = (int) $request->input('variant', 1);
        $audioId = $variant === 2 ? $song->audio_id_2 : $song->audio_id_1;
        if (! $audioId) {
            return response()->json(['error' => 'У трека нет данных для замены фрагмента'], 422);
        }

        $duration = (float) $request->input('infill_end_s') - (float) $request->input('infill_start_s');
        if ($duration < 6 || $duration > 60) {
            return response()->json(['error' => 'Длина заменяемого фрагмента должна быть от 6 до 60 секунд'], 422);
        }

        $result = $this->suno->replaceSection([
            'task_id' => $song->suno_task_id,
            'audio_id' => $audioId,
            'api_source' => $song->api_source,
            'prompt' => $request->input('prompt'),
            'tags' => $request->input('tags'),
            'title' => $request->input('title') ?: $song->title,
            'full_lyrics' => $request->input('full_lyrics'),
            'infill_start_s' => $request->input('infill_start_s'),
            'infill_end_s' => $request->input('infill_end_s'),
            'negative_tags' => $request->input('negative_tags'),
        ]);

        return $this->launch($user, $result, 'replace_section', [
            'title' => $request->input('title') ?: ($song->title.' (правка)'),
            'genre' => $song->genre,
            'lyrics' => $request->input('full_lyrics'),
            'parent_song_id' => $song->id,
            'source_audio_id' => $audioId,
            'api_source' => $song->api_source,
            'model' => $song->model,
        ], billable: false);
    }

    // ---------------------------------------------------------------
    //  Операции над загруженным аудио
    // ---------------------------------------------------------------

    public function uploadCover(Request $request)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user, billable: true)) {
            return $denied;
        }

        $request->validate([
            'upload_url' => 'required|string|url|max:1000',
            'title' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:1000',
            'prompt' => 'nullable|string|max:5000',
            'instrumental' => 'nullable|boolean',
            'vocal_gender' => 'nullable|string|in:m,f',
        ]);

        $result = $this->suno->uploadCover([
            'upload_url' => $request->input('upload_url'),
            'custom_mode' => true,
            'instrumental' => $request->boolean('instrumental'),
            'title' => $request->input('title'),
            'style' => $request->input('style'),
            'prompt' => $request->input('prompt'),
            'vocal_gender' => $request->input('vocal_gender'),
        ]);

        return $this->launch($user, $result, 'upload_cover', [
            'title' => $request->input('title') ?: 'Кавер',
            'genre' => $request->input('style'),
            'lyrics' => $request->input('prompt'),
        ]);
    }

    public function uploadExtend(Request $request)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user, billable: true)) {
            return $denied;
        }

        $request->validate([
            'upload_url' => 'required|string|url|max:1000',
            'title' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:1000',
            'prompt' => 'nullable|string|max:5000',
            'continue_at' => 'nullable|numeric|min:0',
            'instrumental' => 'nullable|boolean',
            'vocal_gender' => 'nullable|string|in:m,f',
        ]);

        $result = $this->suno->uploadExtend([
            'upload_url' => $request->input('upload_url'),
            'title' => $request->input('title'),
            'style' => $request->input('style'),
            'prompt' => $request->input('prompt'),
            'continue_at' => $request->input('continue_at'),
            'instrumental' => $request->boolean('instrumental'),
            'vocal_gender' => $request->input('vocal_gender'),
        ]);

        return $this->launch($user, $result, 'upload_extend', [
            'title' => $request->input('title') ?: 'Продление',
            'genre' => $request->input('style'),
            'lyrics' => $request->input('prompt'),
        ]);
    }

    public function addInstrumental(Request $request)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user, billable: true)) {
            return $denied;
        }

        $request->validate([
            'upload_url' => 'required|string|url|max:1000',
            'title' => 'required|string|max:100',
            'tags' => 'required|string|max:1000',
            'negative_tags' => 'nullable|string|max:1000',
        ]);

        $result = $this->suno->addInstrumental([
            'upload_url' => $request->input('upload_url'),
            'title' => $request->input('title'),
            'tags' => $request->input('tags'),
            'negative_tags' => $request->input('negative_tags', ''),
        ]);

        return $this->launch($user, $result, 'add_instrumental', [
            'title' => $request->input('title'),
            'genre' => $request->input('tags'),
        ]);
    }

    public function addVocals(Request $request)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user, billable: true)) {
            return $denied;
        }

        $request->validate([
            'upload_url' => 'required|string|url|max:1000',
            'prompt' => 'required|string|max:5000',
            'title' => 'required|string|max:100',
            'style' => 'required|string|max:1000',
            'negative_tags' => 'nullable|string|max:1000',
            'vocal_gender' => 'nullable|string|in:m,f',
        ]);

        $result = $this->suno->addVocals([
            'upload_url' => $request->input('upload_url'),
            'prompt' => $request->input('prompt'),
            'title' => $request->input('title'),
            'style' => $request->input('style'),
            'negative_tags' => $request->input('negative_tags', ''),
            'vocal_gender' => $request->input('vocal_gender'),
        ]);

        return $this->launch($user, $result, 'add_vocals', [
            'title' => $request->input('title'),
            'genre' => $request->input('style'),
            'lyrics' => $request->input('prompt'),
        ]);
    }

    public function mashup(Request $request)
    {
        $user = $this->user($request);
        if ($denied = $this->gate($user, billable: true)) {
            return $denied;
        }

        $request->validate([
            'song_ids' => 'nullable|array|size:2',
            'song_ids.*' => 'integer',
            'upload_urls' => 'nullable|array|size:2',
            'upload_urls.*' => 'string|url|max:1000',
            'title' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:1000',
            'prompt' => 'nullable|string|max:5000',
        ]);

        $urls = $request->input('upload_urls', []);

        // Если переданы song_ids — берём публичные URL первых вариантов
        if (empty($urls) && $request->filled('song_ids')) {
            $urls = [];
            foreach ($request->input('song_ids') as $sid) {
                $s = $this->ownedSong($user, (int) $sid);
                if (! $s || ! $s->file_path) {
                    return response()->json(['error' => 'Один из треков недоступен для мэшапа'], 422);
                }
                $urls[] = $s->file_path;
            }
        }

        if (count($urls) !== 2) {
            return response()->json(['error' => 'Для мэшапа нужно ровно 2 трека'], 422);
        }

        $custom = $request->filled('title') || $request->filled('style');

        $result = $this->suno->mashup([
            'upload_urls' => $urls,
            'custom_mode' => $custom,
            'title' => $request->input('title'),
            'style' => $request->input('style'),
            'prompt' => $request->input('prompt'),
        ]);

        return $this->launch($user, $result, 'mashup', [
            'title' => $request->input('title') ?: 'Мэшап',
            'genre' => $request->input('style'),
            'lyrics' => $request->input('prompt'),
        ]);
    }

    // ---------------------------------------------------------------
    //  Внутренние помощники
    // ---------------------------------------------------------------

    private function user(Request $request)
    {
        return $request->get('auth_user');
    }

    private function ownedSong($user, int $songId): ?Song
    {
        return Song::where('id', $songId)
            ->where('user_id', $user->user_id)
            ->first();
    }

    /**
     * Проверка доступа к функционалу (фича-флаг) и баланса.
     * Возвращает JSON-ответ при отказе, либо null если всё ок.
     */
    private function gate($user, bool $billable = false)
    {
        if (! $user) {
            return response()->json(['error' => 'Не авторизован'], 401);
        }

        $allowed = config('services.track_ops.allowed_user_ids', []);
        $unrestricted = empty($allowed) || in_array('*', $allowed, true);

        if (! $unrestricted && ! in_array((string) $user->user_id, $allowed, true)) {
            return response()->json(['error' => 'Функция пока недоступна'], 403);
        }

        if ($billable && $user->balance < 1) {
            return response()->json([
                'error' => 'Недостаточно песен на балансе',
                'need_payment' => true,
            ], 402);
        }

        return null;
    }

    /**
     * Единая точка запуска: списать баланс (если billable), создать
     * дочернюю Song и задиспатчить поллинг статуса.
     */
    private function launch($user, array $result, string $operation, array $attributes, bool $billable = true)
    {
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'error' => $result['error'] ?? 'Не удалось запустить операцию',
                'retry_possible' => $result['retry_possible'] ?? false,
            ], 500);
        }

        if ($billable && in_array($operation, self::BILLABLE_OPS, true)) {
            $user->decrement('balance');
        }

        $song = Song::create(array_merge([
            'user_id' => $user->user_id,
            'operation_type' => $operation,
            'suno_task_id' => $result['task_id'],
        ], $attributes));

        CheckSongGenerationStatus::dispatch($song->id, $result['task_id'], $user->user_id)
            ->delay(now()->addSeconds(10));

        return response()->json([
            'success' => true,
            'task_id' => $result['task_id'],
            'song_id' => $song->id,
        ]);
    }
}
