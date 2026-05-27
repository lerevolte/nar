<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\ChartEntry;
use App\Models\FavoriteSong;
use App\Services\ChartService;
use App\Services\SunoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Главная страница личного кабинета
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        
        // Избранные треки
        $favoriteSongs = FavoriteSong::where('user_id', $user->user_id)
            ->with('song.user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Последние треки (если нет избранных)
        $recentSongs = Song::where('user_id', $user->user_id)
            ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();
        
        $stats = [
            'total_songs' => Song::where('user_id', $user->user_id)->notDeleted()->count(),
            'balance' => $user->balance,
        ];
        
        return view('dashboard.index', compact('favoriteSongs', 'recentSongs', 'stats'));
    }

    /**
     * Страница отдельного трека
     */
    public function showSong(Request $request, $id, ChartService $chartService, SunoService $sunoService)
    {
        $user = $request->get('auth_user');
        
        $song = Song::where('id', $id)
            ->notDeleted()
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        if (!$song->file_path && $song->suno_task_id) {
            $song = $this->tryFetchAndDownloadSunoFiles($song, $sunoService);
        }

        // Получаем текущий чарт
        $chart = $chartService->getOrCreateCurrentChart();

        // Получаем тематический чарт (Valentine)
        $valentineChart = $chartService->getOrCreateThemeChart('valentine');
        $songInValentineChart = false;
        $userHasOtherSongInValentineChart = false;

        if ($valentineChart) {
            $songInValentineChart = ChartEntry::where('chart_id', $valentineChart->id)
                ->where('song_id', $song->id)
                ->exists();

            if (!$songInValentineChart) {
                $userHasOtherSongInValentineChart = ChartEntry::where('chart_id', $valentineChart->id)
                    ->where('user_id', $user->user_id)
                    ->exists();
            }
        }

        // Проверяем, есть ли эта песня в чарте
        $songInChart = ChartEntry::where('chart_id', $chart->id)
            ->where('song_id', $song->id)
            ->exists();

        // Проверяем, есть ли у пользователя другая песня в чарте
        $userHasOtherSongInChart = false;
        if (!$songInChart) {
            $userHasOtherSongInChart = ChartEntry::where('chart_id', $chart->id)
                ->where('user_id', $user->user_id)
                ->exists();
        }

        // Избранные варианты этой песни
        $favoriteVariants = FavoriteSong::where('user_id', $user->user_id)
            ->where('song_id', $song->id)
            ->pluck('variant')
            ->map(fn($v) => $song->id . '_' . $v)
            ->toArray();

        // Проверяем защищён ли трек от удаления
        $isProtected = $songInChart || $songInValentineChart || !empty($favoriteVariants);
        
        return view('dashboard.song', compact(
            'song', 
            'songInChart', 
            'userHasOtherSongInChart',
            'favoriteVariants',
            'valentineChart',
            'songInValentineChart',
            'userHasOtherSongInValentineChart',
            'isProtected'
        ));
    }

    /**
     * Попытка подтянуть файлы с Suno API и скачать на сервер
     */
    protected function tryFetchAndDownloadSunoFiles(Song $song, SunoService $sunoService): Song
    {
        try {
            Log::info("Trying to fetch Suno files for song {$song->id}, task_id: {$song->suno_task_id}");
            
            $result = $sunoService->checkStatus($song->suno_task_id);

            if ($result['status'] === 'completed' && !empty($result['songs'])) {
                $sunoSongs = $result['songs'];
                $updateData = [];
                // Скачиваем первый вариант
                if (!empty($sunoSongs[0]['audio_url'])) {

                    $localUrl = $this->downloadSunoFile(
                        $sunoSongs[0]['audio_url'],
                        $song->user_id,
                        $song->id,
                        'v1'
                    );
                    if ($localUrl) {
                        $updateData['file_path'] = $localUrl;
                        $updateData['audio_id_1'] = $sunoSongs[0]['id'] ?? null;
                    }
                }
                
                // Скачиваем второй вариант
                if (!empty($sunoSongs[1]['audio_url'])) {
                    $localUrl = $this->downloadSunoFile(
                        $sunoSongs[1]['audio_url'],
                        $song->user_id,
                        $song->id,
                        'v2'
                    );
                    if ($localUrl) {
                        $updateData['file_path_2'] = $localUrl;
                        $updateData['audio_id_2'] = $sunoSongs[1]['id'] ?? null;
                    }
                }
                
                if (!empty($updateData)) {
                    $song->update($updateData);
                    $song->refresh();
                    Log::info("Successfully downloaded Suno files for song {$song->id}", $updateData);
                }
            } elseif ($result['status'] === 'processing') {
                Log::info("Song {$song->id} still processing on Suno");
            } else {
                Log::warning("Unexpected Suno status for song {$song->id}", $result);
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch Suno files for song {$song->id}: " . $e->getMessage());
        }

        return $song;
    }

    /**
     * Скачать файл с Suno на сервер
     */
    protected function downloadSunoFile(string $url, int $userId, int $songId, string $prefix): ?string
    {
        $targetDir = public_path('music');
        $baseUrl = config('app.url', 'https://narepite.site') . '/music';
        
        // Создаём директорию если нет
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        try {
            $filename = "{$prefix}_{$songId}_{$userId}_" . Str::random(8) . ".mp3";
            $fullPath = $targetDir . '/' . $filename;

            Log::info("Downloading Suno file: {$url} -> {$fullPath}");

            // Таймаут 60 сек на скачивание
            $response = Http::timeout(60)->get($url);

            if ($response->successful() && strlen($response->body()) > 1000) {
                file_put_contents($fullPath, $response->body());
                $localUrl = "{$baseUrl}/{$filename}";
                Log::info("Downloaded successfully: {$localUrl}");
                return $localUrl;
            }

            Log::warning("Download failed or file too small for URL: {$url}");
            return null;

        } catch (\Exception $e) {
            Log::error("Download error: " . $e->getMessage());
            return null;
        }
    }


    /**
     * API: Список треков пользователя
     */
    public function apiSongs(Request $request)
    {
        $user = $request->get('auth_user');
        
        $songs = Song::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($song) {
                return [
                    'id' => $song->id,
                    'title' => $song->title,
                    'occasion' => $song->occasion,
                    'genre' => $song->genre,
                    'audio_url_1' => $song->file_path,
                    'audio_url_2' => $song->file_path_2,
                    'created_at' => $song->created_at?->format('d.m.Y H:i'),
                ];
            });
        
        return response()->json([
            'songs' => $songs,
            'total' => $songs->count(),
        ]);
    }


    /**
     * Создать временную ссылку для скачивания (требует авторизации)
     */
    public function createDownloadLink(Request $request, $id, $variant = 1)
    {
        $user = $request->get('auth_user');

        // Ищем песню без привязки к пользователю сразу
        $song = Song::find($id);

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }


        // Разрешаем, если пользователь владелец ИЛИ песня есть в любом чарте
        $isOwner = $song->user_id === $user->user_id;
        $isInChart = \App\Models\ChartEntry::where('song_id', $song->id)->exists();

        if (!$isOwner && !$isInChart) {
            return response()->json(['error' => 'Нет прав на скачивание этого трека'], 403);
        }

        // Выбираем файл в зависимости от варианта
        $filePath = $variant == 2 ? $song->file_path_2 : $song->file_path;

        if (!$filePath) {
            return response()->json(['error' => 'Файл не найден'], 404);
        }

        // Генерируем уникальный токен
        $token = Str::random(32);
        
        // Сохраняем в кэш на 5 минут
        Cache::put("download_token:{$token}", [
            'song_id' => $song->id,
            'variant' => $variant,
            'title' => $song->title,
            'file_path' => $filePath,
        ], now()->addMinutes(5));

        $downloadUrl = url("/dl/{$token}");

        return response()->json([
            'success' => true,
            'download_url' => $downloadUrl,
        ]);
    }

    /**
     * Скачать по временной ссылке (БЕЗ авторизации)
     */
    public function downloadByToken(Request $request, $token)
    {
        // Получаем данные из кэша
        $data = Cache::get("download_token:{$token}");

        if (!$data) {
            abort(404, 'Ссылка истекла или недействительна');
        }

        $filePath = $data['file_path'];
        $title = $data['title'];
        $variant = $data['variant'];

        // Формируем имя файла
        $variantSuffix = $variant == 2 ? '_v2' : '';
        $downloadName = Str::slug($title, '_') . $variantSuffix . '.mp3';

        // Если это URL
        if (str_starts_with($filePath, 'http')) {
            $content = @file_get_contents($filePath);
            
            if (!$content) {
                abort(404, 'Не удалось скачать файл');
            }

            // Удаляем токен после использования
            Cache::forget("download_token:{$token}");

            return response($content, 200, [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
                'Content-Length' => strlen($content),
            ]);
        }

        // Локальный файл
        $fullPath = $this->resolveFilePath($filePath);

        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'Файл не найден');
        }

        // Удаляем токен после использования
        Cache::forget("download_token:{$token}");

        return response()->download($fullPath, $downloadName, [
            'Content-Type' => 'audio/mpeg',
        ]);
    }

    /**
     * Определить полный путь к файлу
     */
    private function resolveFilePath(string $filePath): ?string
    {
        $paths = [
            storage_path('app/public/' . str_replace('/storage/', '', $filePath)),
            public_path(ltrim($filePath, '/')),
            public_path('music/' . basename($filePath)),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Мягкое удаление трека
     */
    public function deleteSong(Request $request, $id)
    {
        $user = $request->get('auth_user');

        $song = Song::where('id', $id)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        // Удаляем физические файлы
        $filesToDelete = array_filter([
            $song->file_path,
            $song->file_path_2,
            $song->instrumental_url_1,
            $song->instrumental_url_2,
        ]);

        foreach ($filesToDelete as $fileUrl) {
            // Если файл локальный (в /music/)
            $filename = basename(parse_url($fileUrl, PHP_URL_PATH));
            $localPath = public_path('music/' . $filename);
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }

        // Мягкое удаление — помечаем, но не удаляем из БД
        $song->update([
            'is_deleted' => true,
            'file_path' => null,
            'file_path_2' => null,
            'instrumental_url_1' => null,
            'instrumental_url_2' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Трек удалён']);
    }

    /**
     * Обновить название песни
     */
    public function updateSongTitle(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $user = $request->get('auth_user');

        $song = Song::where('id', $id)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        $song->update(['title' => $request->input('title')]);

        return response()->json([
            'success' => true,
            'title' => $song->title,
        ]);
    }


}