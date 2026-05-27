<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use App\Models\Song;
use App\Services\LyricsGeneratorService;
use App\Services\SunoService;
use App\Services\TelegramNotificationService;
use App\Jobs\CheckSongGenerationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateController extends Controller
{
    /**
     * Страница создания песни
     */
    public function create(Request $request)
    {
        $user = $request->get('auth_user');
        $draft = Draft::where('user_id', $user->user_id)->first();

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
            'breakup' => '💔 Расставание',
            'motivation' => '🔥 Мотивация',
            'corporate' => '🏢 Корпоратив',
            'child' => '👶 Для ребёнка',
            'pet' => '🐾 Про питомца',
            'custom' => '✨ Свой вариант',
        ];

        $genres = [
            'pop' => '🎶 Поп',
            'rap' => '🎤 Рэп / Хип-хоп',
            'rock' => '🎸 Рок',
            'disco' => '🕺 Диско / Ретро',
            'chanson' => '🎻 Шансон',
            'rnb' => '🎷 R&B / Соул',
            'electro' => '⚡ Электронная / Dance',
            'indie' => '🌿 Инди / Альтернатива',
            'jazz' => '🎺 Джаз / Блюз',
            'folk' => '🪕 Фолк / Акустика',
            'metal' => '🤘 Метал / Хард-рок',
            'reggae' => '🌴 Регги',
            'classical' => '🎹 Классика / Оркестр',
            'kids' => '🧸 Детская',
            'romantic' => '🌹 Баллада',
            'party' => '🎊 Клубная / Тусовочная',
            'custom' => '✨ Свой вариант',
        ];

        $genreArtists = [
            'pop' => ['Zivert', 'Егор Крид', 'Ёлка', 'Клава Кока', 'Ханна', 'Дуа Липа', 'Тейлор Свифт', 'The Weeknd', 'Ариана Гранде', 'Джастин Бибер', 'Эд Ширан', 'Билли Айлиш', 'Гарри Стайлс'],
            'rap' => ['Баста', 'Oxxxymiron', 'Скриптонит', 'Miyagi', 'Моргенштерн', 'Хаски', 'Feduk', 'Элджей', 'Big Baby Tape', 'Kizaru', 'Eminem', 'Drake', 'Kendrick Lamar', 'Travis Scott', '50 Cent', 'Post Malone', 'Juice WRLD', 'XXXTentacion', 'Kanye West'],
            'rock' => ['Кино', 'ДДТ', 'Сплин', 'Би-2', 'Мумий Тролль', 'Земфира', 'Linkin Park', 'Queen', 'Nirvana', 'Imagine Dragons', 'Coldplay', 'Green Day', 'Metallica', 'AC/DC', 'The Beatles'],
            'disco' => ['ABBA', 'Boney M', 'Modern Talking', 'Ласковый май', 'Весёлые ребята', 'Дискотека Авария', 'Руки Вверх', 'Bee Gees', 'Donna Summer', 'Earth Wind & Fire'],
            'chanson' => ['Михаил Круг', 'Григорий Лепс', 'Трофим', 'Любэ', 'Ирина Аллегрова', 'Стас Михайлов', 'Александр Розенбаум', 'Вилли Токарев', 'Катя Огонёк', 'Ваенга'],
            'rnb' => ['Jony', 'Rauf & Faik', 'HammAli & Navai', 'Мот', 'Тима Белорусских', 'Beyoncé', 'Rihanna', 'Bruno Mars', 'Usher', 'Chris Brown', 'The Weeknd', 'Frank Ocean', 'SZA', 'Alicia Keys'],
            'electro' => ['Little Big', 'Руки Вверх', 'Imanbek', 'Filatov & Karas', 'David Guetta', 'Calvin Harris', 'Marshmello', 'Tiësto', 'Martin Garrix', 'Kygo', 'Avicii', 'Skrillex', 'Daft Punk'],
            'indie' => ['Монеточка', 'Макс Корж', 'Мукка', 'Три дня дождя', 'Лсп', 'Земфира', 'Звонкий', 'Нервы', 'MATRANG', 'Billie Eilish', 'Lana Del Rey', 'Arctic Monkeys', 'The 1975'],
            'jazz' => ['Фрэнк Синатра', 'Ella Fitzgerald', 'Louis Armstrong', 'Norah Jones', 'Diana Krall', 'Michael Bublé', 'Nina Simone', 'Nat King Cole', 'Amy Winehouse'],
            'folk' => ['Пелагея', 'Мельница', 'Алиса', 'Чиж & Co', 'Ed Sheeran', 'Mumford & Sons', 'Of Monsters and Men', 'The Lumineers', 'Hozier', 'Bon Iver', 'Iron & Wine'],
            'metal' => ['Ария', 'Кипелов', 'Эпидемия', 'Король и Шут', 'Metallica', 'Rammstein', 'Slipknot', 'System of a Down', 'Iron Maiden', 'Black Sabbath', 'Nightwish', 'Linkin Park', 'Stone Sour'],
            'reggae' => ['Miyagi', 'MATRANG', 'Jah Khalib', "5'nizza", 'Bob Marley', 'UB40', 'Shaggy', 'Sean Paul', 'Damian Marley'],
            'classical' => ['Ludovico Einaudi', 'Hans Zimmer', 'Yiruma', 'Max Richter', 'Ólafur Arnalds', 'Эннио Морриконе'],
            'kids' => ['Барбарики', 'Фиксики', 'Маша и Медведь', 'Бременские музыканты', 'Смешарики', 'Три кота'],
            'romantic' => ['Эд Ширан', 'Adele', 'Jony', 'Rauf & Faik', 'Полина Гагарина', 'Сергей Лазарев', 'Дима Билан', 'John Legend', 'Sam Smith', 'Lewis Capaldi', 'Lana Del Rey'],
            'party' => ['Little Big', 'Artik & Asti', 'Элджей', 'Feduk', 'Тимати', 'Инстасамка', 'Slava Marlow', 'Егор Крид', 'Моргенштерн', 'Pitbull', 'Jason Derulo', 'Flo Rida', 'LMFAO', 'Black Eyed Peas'],
        ];

        $languages = LyricsGeneratorService::getLanguages();

        $userVoices = [];
        //if ($user->user_id == 288559694 || $user->user_id == 154483653) {
            $userVoices = \App\Models\UserVoice::forUser($user->user_id)
                ->ready()
                ->get(['id', 'name', 'voice_id'])
                ->toArray();
        //}

        $userPersonas = [];
        //if ($user->user_id == 288559694 || $user->user_id == 154483653) {
            $userPersonas = \App\Models\UserPersona::forUser($user->user_id)
                ->ready()
                ->get(['id', 'name', 'persona_id', 'description'])
                ->toArray();
        //}

        return view('generate.create', compact('draft', 'occasions', 'genres', 'genreArtists', 'languages', 'userVoices', 'userPersonas'));
    }

    /**
     * API: Генерация текста — теперь с vocal_gender
     */
    public function generateLyrics(Request $request, LyricsGeneratorService $lyricsService)
    {
        $request->validate([
            'occasion' => 'required|string|max:255',
            'genre' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'language' => 'required|string|in:ru,en,de,es,fr,it',
            'artist' => 'nullable|string|max:255',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
        ]);

        $user = $request->get('auth_user');

        $result = $lyricsService->generate([
            'occasion' => $request->input('occasion'),
            'genre' => $request->input('genre'),
            'description' => $request->input('description'),
            'language' => $request->input('language'),
            'artist' => $request->input('artist'),
            'vocal_gender' => $request->input('vocal_gender'),
        ]);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        // Сохраняем черновик
        Draft::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'occasion' => $request->input('occasion'),
                'occasion_text' => $request->input('occasion'),
                'genre' => $request->input('genre'),
                'genre_text' => $request->input('genre'),
                'description' => $request->input('description'),
                'lyrics' => $result['lyrics'],
                'song_title' => $result['title'],
            ]
        );

        // Prepare lyrics for display (translate tags to Russian)
        $displayLyrics = LyricsGeneratorService::prepareLyricsForUser($result['lyrics']);

        return response()->json([
            'success' => true,
            'title' => $result['title'],
            'lyrics' => $result['lyrics'],           // raw (with English tags)
            'display_lyrics' => $displayLyrics,       // for UI display
            'comment' => $result['comment'],
        ]);
    }

    /**
     * API: Перевод текста
     */
    public function translateLyrics(Request $request, LyricsGeneratorService $lyricsService)
    {
        $request->validate([
            'lyrics' => 'required|string',
            'target_language' => 'required|string|in:ru,en,de,es,fr,it',
        ]);

        $result = $lyricsService->translate(
            $request->input('lyrics'),
            $request->input('target_language')
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        $user = $request->get('auth_user');
        $draft = Draft::where('user_id', $user->user_id)->first();
        if ($draft) {
            $draft->update(['lyrics' => $result['lyrics']]);
        }

        return response()->json([
            'success' => true,
            'lyrics' => $result['lyrics'],
        ]);
    }

    /**
     * API: Улучшение текста — теперь с vocal_gender
     */
    public function improveLyrics(Request $request, LyricsGeneratorService $lyricsService)
    {
        $request->validate([
            'lyrics' => 'required|string',
            'feedback' => 'required|string|max:1000',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
        ]);

        $user = $request->get('auth_user');
        $draft = Draft::where('user_id', $user->user_id)->first();

        $result = $lyricsService->improve(
            $request->input('lyrics'),
            $request->input('feedback'),
            [
                'occasion' => $draft->occasion ?? '',
                'genre' => $draft->genre ?? '',
                'artist' => $request->input('artist'),
                'vocal_gender' => $request->input('vocal_gender'),
            ]
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        if ($draft) {
            $draft->update([
                'lyrics' => $result['lyrics'],
                'song_title' => $result['title'],
            ]);
        }

        $displayLyrics = LyricsGeneratorService::prepareLyricsForUser($result['lyrics']);

        return response()->json([
            'success' => true,
            'title' => $result['title'],
            'lyrics' => $result['lyrics'],
            'display_lyrics' => $displayLyrics,
            'comment' => $result['comment'],
        ]);
    }

    /**
     * API: Форматирование структуры своего текста
     */
    public function formatStructure(Request $request, LyricsGeneratorService $lyricsService)
    {
        $request->validate([
            'lyrics' => 'required|string',
        ]);

        $result = $lyricsService->formatStructure($request->input('lyrics'));

        if (!$result['success']) {
            return response()->json(['error' => $result['error'] ?? 'Ошибка'], 500);
        }

        // Update draft
        $user = $request->get('auth_user');
        $draft = Draft::where('user_id', $user->user_id)->first();
        if ($draft) {
            $draft->update(['lyrics' => $result['lyrics']]);
        }

        return response()->json([
            'success' => true,
            'lyrics' => $result['lyrics'],
            'display_lyrics' => LyricsGeneratorService::prepareLyricsForUser($result['lyrics']),
        ]);
    }

    /**
     * API: Запуск генерации музыки — vocal_gender: m, f, duet, random
     */
    public function generateMusic(Request $request, SunoService $sunoService, LyricsGeneratorService $lyricsService)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'lyrics' => 'required|string',
            'genre' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'vocal_gender' => 'sometimes|nullable|string|in:m,f,duet,random',
            'voice_id' => 'nullable|string|max:255',
            'persona_id' => 'nullable|string|max:255',
        ]);

        $user = $request->get('auth_user');

        if ($user->balance < 1) {
            return response()->json([
                'error' => 'Недостаточно песен на балансе',
                'need_payment' => true,
            ], 402);
        }

        $title = $lyricsService->ensureTitle(
            $request->input('title'),
            $request->input('lyrics')
        );


        $vocalGender = $request->input('vocal_gender', 'random');

        // Prepare lyrics for Suno (translate RU tags to EN, add voice tags)
        $sunoLyrics = LyricsGeneratorService::prepareLyricsForSuno(
            $request->input('lyrics'),
            $vocalGender
        );

        // For Suno API: m, f or null (duet uses tags in lyrics, random = null)
        $sunoGender = null;
        if (in_array($vocalGender, ['m', 'f', 'duet'])) {
            $sunoGender = $vocalGender;
        }
        // duet and random -> null

        $artist = $request->input('artist', '');
        $genre = $request->input('genre', '');
        $styleForSuno = $artist ?: $genre;

        $personaId = $request->input('persona_id');
        $personaSource = null;

        if ($personaId) {
            $voice = \App\Models\UserVoice::where('voice_id', $personaId)->first();
            if ($voice) {
                $personaSource = 'kie';

                // Проверяем доступность голоса
                $voiceService = app(\App\Services\VoiceService::class);
                $check = $voiceService->checkAvailability($voice->generate_task_id ?: $voice->task_id);

                if (!($check['is_available'] ?? false)) {
                    $voice->update(['status' => 'expired', 'is_available' => false]);

                    return response()->json([
                        'error' => 'Голос «' . $voice->name . '» истёк. Пересоздайте его в разделе «Мои голоса».',
                        'voice_expired' => true,
                        'voice_id' => $voice->id,
                    ], 400);
                }
            }
        }

        $result = $sunoService->generateMusic([
            'lyrics' => $sunoLyrics,
            'title' => $title,
            'genre' => $styleForSuno,
            'vocal_gender' => $sunoGender,
            'persona_id' => $personaId,
            'persona_source' => $personaSource,
            'is_promo' => false,
        ]);

        

        if (!$result['success']) {
            if (!empty($result['persona_expired']) && $personaId) {
                // Помечаем персону/голос как истёкшие
                \App\Models\UserPersona::where('persona_id', $personaId)
                    ->update(['status' => 'expired']);
                \App\Models\UserVoice::where('voice_id', $personaId)
                    ->update(['status' => 'expired', 'is_available' => false]);

                return response()->json([
                    'error' => 'Голос или персона истекли. Выберите другой вариант или пересоздайте в разделе «Мои голоса».',
                    'voice_expired' => true,
                ], 400);
            }

            return response()->json(['error' => $result['error']], 500);
        }

        $user->decrement('balance');

        $draft = Draft::where('user_id', $user->user_id)->first();

        // Save lyrics for user display (translated tags)
        $displayLyrics = LyricsGeneratorService::prepareLyricsForUser($request->input('lyrics'));

        $song = Song::create([
            'user_id' => $user->user_id,
            'title' => $title,
            'occasion' => $draft->occasion ?? null,
            'genre' => $request->input('genre'),
            'description' => $draft->description ?? null,
            'lyrics' => $displayLyrics,
            'suno_task_id' => $result['task_id'],
            'api_source' => $personaSource === 'kie' ? 'kie' : null,
        ]);

        Draft::where('user_id', $user->user_id)->delete();

        CheckSongGenerationStatus::dispatch($song->id, $result['task_id'], $user->user_id)
            ->delay(now()->addSeconds(10));

        return response()->json([
            'success' => true,
            'task_id' => $result['task_id'],
            'song_id' => $song->id,
        ]);
    }

    /**
     * API: Проверка статуса генерации
     */
    public function checkStatus(Request $request, SunoService $sunoService)
    {
        $request->validate([
            'task_id' => 'required|string',
            'song_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $taskId = $request->input('task_id');
        $songId = $request->input('song_id');

        $song = Song::where('id', $songId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Song not found'], 404);
        }

        $result = $sunoService->checkStatus($taskId, $song->api_source);

        if ($result['status'] === 'completed' && !empty($result['songs'])) {
            $sunoSongs = $result['songs'];
            $updateData = [];

            if (!empty($sunoSongs[0]['audio_url']) && !$song->file_path) {
                $localUrl = $this->downloadSunoFile($sunoSongs[0]['audio_url'], $song->user_id, $song->id, 'v1');
                if ($localUrl) {
                    $updateData['file_path'] = $localUrl;
                    $updateData['audio_id_1'] = $sunoSongs[0]['id'] ?? null;
                }
            }

            if (!empty($sunoSongs[1]['audio_url']) && !$song->file_path_2) {
                $localUrl = $this->downloadSunoFile($sunoSongs[1]['audio_url'], $song->user_id, $song->id, 'v2');
                if ($localUrl) {
                    $updateData['file_path_2'] = $localUrl;
                    $updateData['audio_id_2'] = $sunoSongs[1]['id'] ?? null;
                }
            }
            // Скачиваем обложку
            if (!empty($sunoSongs[0]['image_url']) && !$song->cover_url) {
                $coverUrl = $this->downloadSunoCover($sunoSongs[0]['image_url'], $song->user_id, $song->id);
                if ($coverUrl) {
                    $updateData['cover_url'] = $coverUrl;
                }
            }

            if (!empty($updateData)) {
                $song->update($updateData);
            }
        }

        return response()->json($result);
    }

    /**
     * Скачать файл с Suno на сервер
     */
    protected function downloadSunoFile(string $url, int $userId, int $songId, string $prefix): ?string
    {
        $targetDir = public_path('music');
        $baseUrl = 'https://narepite.site/music';

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        try {
            $filename = "{$prefix}_{$songId}_{$userId}_" . Str::random(8) . ".mp3";
            $fullPath = $targetDir . '/' . $filename;

            $response = Http::timeout(60)->get($url);

            if ($response->successful() && strlen($response->body()) > 1000) {
                file_put_contents($fullPath, $response->body());
                return "{$baseUrl}/{$filename}";
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Download error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Скачать обложку с Suno на сервер
     */
    public function downloadSunoCover(string $url, int $userId, int $songId): ?string
    {
        $targetDir = public_path('covers');
        $baseUrl = 'https://narepite.site/covers';
 
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
 
        try {
            $ext = 'jpeg';
            if (str_contains($url, '.png')) $ext = 'png';
            elseif (str_contains($url, '.webp')) $ext = 'webp';
 
            $filename = "cover_{$songId}_{$userId}_" . Str::random(8) . ".{$ext}";
            $fullPath = $targetDir . '/' . $filename;
 
            $response = Http::timeout(30)->get($url);
 
            if ($response->successful() && strlen($response->body()) > 500) {
                file_put_contents($fullPath, $response->body());
                Log::info("Cover downloaded: {$filename}");
                return "{$baseUrl}/{$filename}";
            }
 
            Log::warning("Cover download failed: {$url}");
            return null;
        } catch (\Exception $e) {
            Log::error("Cover download error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * API: Получить обложку для песни (подкачка из Suno)
     */
    public function fetchCover(Request $request, SunoService $sunoService)
    {
        $request->validate(['song_id' => 'required|integer']);
 
        $user = $request->get('auth_user');
        $song = Song::where('id', $request->input('song_id'))
            ->where('user_id', $user->user_id)
            ->first();
 
        if (!$song) return response()->json(['error' => 'Песня не найдена'], 404);
        if ($song->cover_url) return response()->json(['success' => true, 'cover_url' => $song->cover_url]);
        if (!$song->suno_task_id) return response()->json(['error' => 'Нет данных генерации'], 400);
 
        $result = $sunoService->checkStatus($song->suno_task_id);
 
        if ($result['status'] !== 'completed' || empty($result['songs'])) {
            return response()->json(['error' => 'Не удалось получить данные из Suno'], 400);
        }
 
        $imageUrl = $result['songs'][0]['image_url'] ?? null;
        if (!$imageUrl) {
            return response()->json(['error' => 'Обложка недоступна'], 400);
        }
 
        $coverUrl = $this->downloadSunoCover($imageUrl, $song->user_id, $song->id);
        if (!$coverUrl) {
            return response()->json(['error' => 'Не удалось скачать обложку'], 500);
        }
 
        $song->update(['cover_url' => $coverUrl]);
 
        return response()->json([
            'success' => true,
            'cover_url' => $coverUrl,
        ]);
    }

    /**
     * API: Восстановление трека — подкачка файлов из Suno по suno_task_id
     */
    public function restoreSong(Request $request, SunoService $sunoService)
    {
        $request->validate([
            'song_id' => 'required|integer',
        ]);

        $user = $request->get('auth_user');
        $songId = $request->input('song_id');

        $song = Song::where('id', $songId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        // Если файлы уже есть — не нужно восстанавливать
        if ($song->file_path && file_exists(public_path('music/' . basename(parse_url($song->file_path, PHP_URL_PATH))))) {
            return response()->json(['error' => 'Файл уже существует'], 400);
        }

        if (!$song->suno_task_id) {
            return response()->json(['error' => 'Невозможно восстановить: нет данных генерации'], 400);
        }

        // Запрашиваем файлы из Suno API
        $result = $sunoService->checkStatus($song->suno_task_id);

        if ($result['status'] !== 'completed' || empty($result['songs'])) {
            return response()->json([
                'error' => 'Не удалось получить файлы. Возможно, данные генерации устарели.',
            ], 400);
        }

        $sunoSongs = $result['songs'];
        $updateData = ['files_removed' => 0];
        $restored = 0;

        if (!empty($sunoSongs[0]['audio_url'])) {
            $localUrl = $this->downloadSunoFile($sunoSongs[0]['audio_url'], $song->user_id, $song->id, 'v1');
            if ($localUrl) {
                $updateData['file_path'] = $localUrl;
                $updateData['audio_id_1'] = $sunoSongs[0]['id'] ?? $song->audio_id_1;
                $restored++;
            }
        }

        if (!empty($sunoSongs[1]['audio_url'])) {
            $localUrl = $this->downloadSunoFile($sunoSongs[1]['audio_url'], $song->user_id, $song->id, 'v2');
            if ($localUrl) {
                $updateData['file_path_2'] = $localUrl;
                $updateData['audio_id_2'] = $sunoSongs[1]['id'] ?? $song->audio_id_2;
                $restored++;
            }
        }

        if ($restored === 0) {
            return response()->json(['error' => 'Не удалось скачать файлы'], 500);
        }

        $song->update($updateData);

        Log::info("Song restored", [
            'song_id' => $song->id,
            'user_id' => $user->user_id,
            'restored_files' => $restored,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Восстановлено {$restored} " . ($restored === 1 ? 'файл' : 'файла'),
        ]);
    }
}