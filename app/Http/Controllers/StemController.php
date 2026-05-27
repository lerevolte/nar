<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Services\SunoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StemController extends Controller
{
    /**
     * API: Запуск разделения голоса и минусовки
     *
     * Логика:
     * 1. Если уже разделено (instrumental_url есть) — отдаём кэш
     * 2. Если task_id уже есть (в процессе) — подхватываем ожидание
     * 3. Иначе — запускаем новое разделение
     */
    public function separate(Request $request, SunoService $sunoService)
    {
        $request->validate([
            'song_id' => 'required|integer',
            'variant' => 'required|integer|in:1,2',
        ]);

        $user = $request->get('auth_user');
        $songId = (int) $request->input('song_id');
        $variant = (int) $request->input('variant');

        $song = Song::where('id', $songId)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$song) {
            return response()->json(['error' => 'Песня не найдена'], 404);
        }

        // === 1. КЭШИРОВАНО — уже разделено ===
        $instField = $variant === 2 ? 'instrumental_url_2' : 'instrumental_url_1';
        $vocalField = $variant === 2 ? 'vocal_url_2' : 'vocal_url_1';
        $taskField = $variant === 2 ? 'stem_task_id_2' : 'stem_task_id_1';

        if ($song->$instField) {
            return response()->json([
                'success' => true,
                'cached' => true,
                'instrumental_url' => $song->$instField,
                'vocal_url' => $song->$vocalField,
            ]);
        }

        // === 2. УЖЕ В ПРОЦЕССЕ — подхватываем ожидание ===
        $existingTaskId = $song->$taskField;

        if ($existingTaskId) {
            Log::info("Stem separation already running for song {$songId} v{$variant}, task={$existingTaskId}. Resuming wait...");

            $result = $this->waitAndSave($sunoService, $song, $existingTaskId, $variant);

            if ($result['success']) {
                return response()->json($result);
            }

            // Если провалилось — сбрасываем task_id, чтобы можно было запустить заново
            $song->update([$taskField => null]);

            return response()->json([
                'error' => 'Ошибка разделения: ' . ($result['error'] ?? 'Unknown'),
            ], 500);
        }

        // === 3. НОВЫЙ ЗАПУСК ===
        $audioId = $variant === 2 ? $song->audio_id_2 : $song->audio_id_1;
        $sunoTaskId = $song->suno_task_id;

        if (!$audioId || !$sunoTaskId) {
            return response()->json([
                'error' => 'Невозможно разделить этот трек (нет данных audio_id или suno_task_id)',
            ], 400);
        }

        $separateResult = $sunoService->separateVocals($sunoTaskId, $audioId);

        if (!$separateResult['success']) {
            return response()->json([
                'error' => 'Ошибка API: ' . ($separateResult['error'] ?? 'Unknown'),
            ], 500);
        }

        $newTaskId = $separateResult['task_id'];

        // Сохраняем task_id в БД — защита от повторного запуска
        $song->update([$taskField => $newTaskId]);

        Log::info("Stem separation started for song {$songId} v{$variant}, task={$newTaskId}");

        // Ждём результат
        $result = $this->waitAndSave($sunoService, $song, $newTaskId, $variant);

        if ($result['success']) {
            return response()->json($result);
        }

        // Сбрасываем task_id при ошибке
        $song->update([$taskField => null]);

        return response()->json([
            'error' => 'Ошибка разделения: ' . ($result['error'] ?? 'Timeout'),
        ], 500);
    }

    /**
     * Ожидание результата + сохранение файлов
     */
    private function waitAndSave(SunoService $sunoService, Song $song, string $taskId, int $variant): array
    {
        $result = $sunoService->waitForVocalSeparation($taskId, 300, 15);

        $taskField = $variant === 2 ? 'stem_task_id_2' : 'stem_task_id_1';
        $instField = $variant === 2 ? 'instrumental_url_2' : 'instrumental_url_1';
        $vocalField = $variant === 2 ? 'vocal_url_2' : 'vocal_url_1';

        if ($result['status'] !== 'completed') {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Timeout',
            ];
        }

        $instUrl = $result['instrumental_url'] ?? '';
        $vocalUrl = $result['vocal_url'] ?? '';

        // Скачиваем на сервер
        $localInstUrl = $this->downloadStemFile($instUrl, $song->user_id, $song->id, "inst_v{$variant}");
        $localVocalUrl = $this->downloadStemFile($vocalUrl, $song->user_id, $song->id, "vocal_v{$variant}");

        // Сохраняем в БД + очищаем task_id
        $song->update([
            $instField => $localInstUrl ?: $instUrl,
            $vocalField => $localVocalUrl ?: $vocalUrl,
            $taskField => null, // задача завершена
        ]);

        Log::info("Stem separation completed for song {$song->id} v{$variant}");

        return [
            'success' => true,
            'cached' => false,
            'instrumental_url' => $localInstUrl ?: $instUrl,
            'vocal_url' => $localVocalUrl ?: $vocalUrl,
        ];
    }

    /**
     * Скачать стем на сервер
     */
    private function downloadStemFile(string $url, int $userId, int $songId, string $prefix): ?string
    {
        if (empty($url)) return null;

        $targetDir = public_path('music');
        $baseUrl = config('app.url', 'https://narepite.site') . '/music';

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

            Log::warning("Stem download failed for URL: {$url}");
            return null;

        } catch (\Exception $e) {
            Log::error("Stem download error: " . $e->getMessage());
            return null;
        }
    }
}