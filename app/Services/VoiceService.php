<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.kie.ai/api/v1';

    public function __construct()
    {
       $this->apiKey = config('services.kie.api_key');
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Шаг 1: Отправляем аудио → получаем taskId, потом поллим phrase
     */
    public function requestVerifyPhrase(string $audioUrl, int $vocalStartS, int $vocalEndS, string $language = 'ru'): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post("{$this->baseUrl}/voice/validate", [
                    'voiceUrl' => $audioUrl,
                    'vocalStartS' => $vocalStartS,
                    'vocalEndS' => $vocalEndS,
                    'language' => $language,
                ]);

            $data = $response->json();
            Log::info("Voice validate response: status={$response->status()} body=" . $response->body());

            if (($data['code'] ?? 0) == 200 && !empty($data['data']['taskId'])) {
                return ['success' => true, 'task_id' => $data['data']['taskId']];
            }

            return ['success' => false, 'error' => $data['msg'] ?? 'Ошибка API'];
        } catch (\Exception $e) {
            Log::error("Voice validate error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Поллинг: получаем verify phrase по taskId
     */
    public function getValidateInfo(string $taskId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$this->baseUrl}/voice/validate-info", [
                    'taskId' => $taskId,
                ]);

            $data = $response->json();
            Log::info("Voice validate info: " . json_encode($data));

            if (($data['code'] ?? 0) != 200) {
                return ['status' => 'processing'];
            }

            $taskData = $data['data'] ?? [];
            $status = $taskData['status'] ?? 'PENDING';

            if ($status === 'SUCCESS' || $status === 'wait_validating') {
                $phrase = $taskData['validateInfo']
                    ?? ($taskData['response']['verifyPhrase'] ?? '')
                    ?: ($taskData['response']['verify_phrase'] ?? '');

                if ($phrase) {
                    return [
                        'status' => 'ready',
                        'verify_phrase' => $phrase,
                    ];
                }
            }

            if (in_array($status, ['FAILED', 'ERROR'])) {
                return ['status' => 'failed', 'error' => $taskData['errorMessage'] ?? 'Ошибка'];
            }

            return ['status' => 'processing'];
        } catch (\Exception $e) {
            Log::error("Voice validate info error: " . $e->getMessage());
            return ['status' => 'processing'];
        }
    }

    /**
     * Шаг 2: Отправляем verify-аудио → генерируем голос
     */
    public function generateVoice(string $taskId, string $verifyAudioUrl, string $name = '', string $description = '', string $style = ''): array
    {
        try {
            $payload = [
                'taskId' => $taskId,
                'verifyUrl' => $verifyAudioUrl,
            ];
            if ($name) $payload['voiceName'] = $name;
            if ($description) $payload['description'] = $description;
            if ($style) $payload['style'] = $style;

            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post("{$this->baseUrl}/voice/generate", $payload);

            $data = $response->json();
            Log::info("Voice generate response: " . json_encode($data));

            if (($data['code'] ?? 0) == 200 && !empty($data['data']['taskId'])) {
                return ['success' => true, 'task_id' => $data['data']['taskId']];
            }

            return ['success' => false, 'error' => $data['msg'] ?? 'Ошибка'];
        } catch (\Exception $e) {
            Log::error("Voice generate error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Поллинг: статус генерации голоса → voiceId
     */
    public function getRecordInfo(string $taskId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$this->baseUrl}/voice/record-info", [
                    'taskId' => $taskId,
                ]);

            $data = $response->json();
            Log::info("Voice record info: " . json_encode($data));

            if (($data['code'] ?? 0) != 200) {
                return ['status' => 'processing'];
            }

            $taskData = $data['data'] ?? [];
            $status = $taskData['status'] ?? 'PENDING';

            if (strtolower($status) === 'success') {
                $voiceId = $taskData['voiceId']
                    ?? ($taskData['response']['voiceId'] ?? '')
                    ?: ($taskData['response']['voice_id'] ?? '');

                return [
                    'status' => 'ready',
                    'voice_id' => $voiceId,
                ];
            }

            if (in_array(strtolower($status), ['failed', 'error'])) {
                return ['status' => 'failed', 'error' => $taskData['errorMessage'] ?? 'Ошибка'];
            }

            return ['status' => 'processing'];
        } catch (\Exception $e) {
            Log::error("Voice record info error: " . $e->getMessage());
            return ['status' => 'processing'];
        }
    }

    /**
     * Проверка доступности голоса
     */
    public function checkAvailability(string $taskId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/voice/check-voice", [
                    'task_id' => $taskId,
                ]);

            $data = $response->json();

            if (($data['code'] ?? 0) == 200) {
                return [
                    'success' => true,
                    'is_available' => $data['data']['isAvailable'] ?? false,
                ];
            }

            return ['success' => false, 'error' => $data['msg'] ?? 'Ошибка'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Перегенерация фразы для истёкшего голоса
     */
    public function regeneratePhrase(string $taskId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post("{$this->baseUrl}/voice/regenerate", [
                    'taskId' => $taskId,
                ]);

            $data = $response->json();

            if (($data['code'] ?? 0) == 200 && !empty($data['data']['taskId'])) {
                return ['success' => true, 'task_id' => $data['data']['taskId']];
            }

            return ['success' => false, 'error' => $data['msg'] ?? 'Ошибка'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}