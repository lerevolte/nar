<?php

namespace App\Http\Controllers;

use App\Models\UserVoice;
use App\Services\VoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoiceController extends Controller
{
    private function ensureAllowed(Request $request)
    {
        $user = $request->get('auth_user');
        // Только для тестового юзера пока
        if (!$user) {
            abort(403);
        }
        return $user;
    }

    /**
     * Список голосов юзера
     */
    public function index(Request $request)
    {
        $user = $this->ensureAllowed($request);
        $voices = UserVoice::forUser($user->user_id)->orderByDesc('id')->get();
        return response()->json(['voices' => $voices]);
    }

    /**
     * Загрузка исходного аудио
     */
    public function uploadAudio(Request $request)
    {
        $user = $this->ensureAllowed($request);

        $request->validate([
            'audio' => 'required|file|max:20480|mimes:mp3,wav,m4a,ogg,webm',
        ]);

        $file = $request->file('audio');
        $dir = public_path('uploads/voices');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name = time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        $url = 'https://narepite.site/uploads/voices/' . $name;

        return response()->json(['success' => true, 'url' => $url]);
    }

    /**
     * Шаг 1: Создать голос + запросить verify phrase
     */
    public function create(Request $request, VoiceService $voiceService)
    {
        $user = $this->ensureAllowed($request);

        $request->validate([
            'name' => 'required|string|max:100',
            'source_audio_url' => 'required|string|url|max:500',
            'vocal_start' => 'required|integer|min:0',
            'vocal_end' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'style' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:5',
        ]);

        $result = $voiceService->requestVerifyPhrase(
            $request->input('source_audio_url'),
            $request->input('vocal_start'),
            $request->input('vocal_end'),
            $request->input('language', 'ru')
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        $voice = UserVoice::create([
            'user_id' => $user->user_id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'style' => $request->input('style'),
            'source_audio_url' => $request->input('source_audio_url'),
            'task_id' => $result['task_id'],
            'status' => 'validating',
        ]);

        return response()->json(['success' => true, 'voice_id' => $voice->id, 'task_id' => $result['task_id']]);
    }

    /**
     * Поллинг: статус verify phrase
     */
    public function checkPhraseStatus(Request $request, VoiceService $voiceService)
    {
        $user = $this->ensureAllowed($request);

        $voice = UserVoice::forUser($user->user_id)->findOrFail($request->input('voice_id'));

        if ($voice->status === 'phrase_ready') {
            return response()->json(['status' => 'ready', 'verify_phrase' => $voice->verify_phrase]);
        }

        $result = $voiceService->getValidateInfo($voice->task_id);

        if ($result['status'] === 'ready') {
            $voice->update([
                'verify_phrase' => $result['verify_phrase'],
                'status' => 'phrase_ready',
            ]);
            return response()->json(['status' => 'ready', 'verify_phrase' => $result['verify_phrase']]);
        }

        if ($result['status'] === 'failed') {
            $voice->update(['status' => 'failed', 'error_message' => $result['error'] ?? 'Ошибка']);
            return response()->json(['status' => 'failed', 'error' => $result['error']]);
        }

        return response()->json(['status' => 'processing']);
    }

    /**
     * Шаг 2: Загрузить verify-аудио → сгенерировать голос
     */
    public function submitVerifyAudio(Request $request, VoiceService $voiceService)
    {
        $user = $this->ensureAllowed($request);

        $request->validate([
            'voice_id' => 'required|integer',
            'verify_audio_url' => 'required|string|url|max:500',
        ]);

        $voice = UserVoice::forUser($user->user_id)->findOrFail($request->input('voice_id'));

        $result = $voiceService->generateVoice(
            $voice->task_id,
            $request->input('verify_audio_url'),
            $voice->name,
            $voice->description ?? '',
            $voice->style ?? ''
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        $voice->update([
            'verify_audio_url' => $request->input('verify_audio_url'),
            'generate_task_id' => $result['task_id'],
            'status' => 'generating',
        ]);

        return response()->json(['success' => true, 'task_id' => $result['task_id']]);
    }

    /**
     * Поллинг: статус генерации голоса
     */
    public function checkVoiceStatus(Request $request, VoiceService $voiceService)
    {
        $user = $this->ensureAllowed($request);

        $voice = UserVoice::forUser($user->user_id)->findOrFail($request->input('voice_id'));

        if ($voice->status === 'ready' && $voice->voice_id) {
            return response()->json(['status' => 'ready', 'voice_id' => $voice->voice_id]);
        }

        $taskId = $voice->generate_task_id ?: $voice->task_id;
        $result = $voiceService->getRecordInfo($taskId);

        if ($result['status'] === 'ready' && !empty($result['voice_id'])) {
            $voice->update([
                'voice_id' => $result['voice_id'],
                'status' => 'ready',
                'is_available' => true,
            ]);
            return response()->json(['status' => 'ready', 'voice_id' => $result['voice_id']]);
        }

        if ($result['status'] === 'failed') {
            $voice->update(['status' => 'failed', 'error_message' => $result['error'] ?? 'Ошибка']);
            return response()->json(['status' => 'failed', 'error' => $result['error']]);
        }

        return response()->json(['status' => 'processing']);
    }

    /**
     * Удалить голос
     */
    public function destroy(Request $request, $id)
    {
        $user = $this->ensureAllowed($request);
        UserVoice::forUser($user->user_id)->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function page(Request $request)
    {
        $user = $this->ensureAllowed($request);
        $voices = UserVoice::forUser($user->user_id)->orderByDesc('id')->get();

        // Проверяем доступность ready-голосов
        $voiceService = app(VoiceService::class);
        foreach ($voices as $voice) {
            if ($voice->status === 'ready' && $voice->is_available) {
                $taskId = $voice->generate_task_id ?: $voice->task_id;
                $check = $voiceService->checkAvailability($taskId);
                if (isset($check['is_available']) && !$check['is_available']) {
                    $voice->update(['status' => 'expired', 'is_available' => false]);
                    $voice->status = 'expired';
                    $voice->is_available = false;
                }
            }
        }

        $personas = \App\Models\UserPersona::forUser($user->user_id)->orderByDesc('id')->get();

        return view('voices.index', compact('voices', 'personas'));
    }

    public function recreate(Request $request, VoiceService $voiceService)
    {
        $user = $this->ensureAllowed($request);

        $voice = UserVoice::forUser($user->user_id)->findOrFail($request->input('voice_id'));

        $result = $voiceService->regeneratePhrase($voice->task_id);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        $voice->update([
            'task_id' => $result['task_id'],
            'status' => 'validating',
            'voice_id' => null,
            'generate_task_id' => null,
            'verify_phrase' => null,
            'verify_audio_url' => null,
            'is_available' => false,
            'error_message' => null,
        ]);

        return response()->json([
            'success' => true,
            'voice_id' => $voice->id,
            'task_id' => $result['task_id'],
        ]);
    }
}