<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MetricaDebugReport extends Command
{
    protected $signature = 'metrica:debug-report {--hours=48}';
    protected $description = 'Генерирует отладочный отчёт и CSV для техподдержки Яндекс.Метрики';

    private const COUNTER_ID = 105879987;

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $token = config('services.yandex_metrica.oauth_token');
        $outputDir = storage_path('app');

        // 1. Собираем платежи
        $payments = DB::table('payments')
            ->where('status', 'succeeded')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('id')
            ->get();

        $userIds = $payments->pluck('user_id')->unique()->toArray();
        $ymClients = DB::table('users')
            ->whereIn('user_id', $userIds)
            ->pluck('ym_client_id', 'user_id')
            ->toArray();

        // 2. Генерируем CSV файлы
        $clientIdRows = [];
        $userIdRows = [];

        foreach ($payments as $p) {
            $goalName = $this->resolveGoalName($p);
            $timestamp = strtotime($p->updated_at ?: $p->created_at);
            $price = (float) $p->amount;
            $ymClientId = $ymClients[$p->user_id] ?? null;

            if ($ymClientId && preg_match('/^\d{10,20}$/', $ymClientId)) {
                $clientIdRows[] = [$ymClientId, $goalName, $timestamp, $price, 'RUB'];
            } else {
                $userIdRows[] = [$p->user_id, $goalName, $timestamp, $price, 'RUB'];
            }
        }

        // Сохраняем CSV
        if (!empty($clientIdRows)) {
            $csv = $this->buildCsv(['ClientId', 'Target', 'DateTime', 'Price', 'Currency'], $clientIdRows);
            $path = $outputDir . '/metrica_client_id.csv';
            file_put_contents($path, $csv);
            $this->info("CSV ClientId: {$path} (" . count($clientIdRows) . " строк)");
        }

        if (!empty($userIdRows)) {
            $csv = $this->buildCsv(['UserId', 'Target', 'DateTime', 'Price', 'Currency'], $userIdRows);
            $path = $outputDir . '/metrica_user_id.csv';
            file_put_contents($path, $csv);
            $this->info("CSV UserId: {$path} (" . count($userIdRows) . " строк)");
        }

        // 3. Проверяем статус последних загрузок
        $this->line("\n--- Статус загрузок в Метрике ---");

        $response = Http::withHeaders([
            'Authorization' => "OAuth {$token}",
        ])->get("https://api-metrica.yandex.net/management/v1/counter/" . self::COUNTER_ID . "/offline_conversions/uploadings");

        if ($response->successful()) {
            $data = $response->json();
            $uploadings = $data['uploadings'] ?? [];

            // Последние 10
            $recent = array_slice($uploadings, 0, 10);
            foreach ($recent as $u) {
                $this->line(sprintf(
                    "  ID: %s | %s | type: %s | id_type: %s | lines: %d | status: %s",
                    $u['id'],
                    $u['create_time'] ?? '?',
                    $u['type'] ?? '?',
                    $u['client_id_type'] ?? '?',
                    $u['line_quantity'] ?? 0,
                    $u['status'] ?? '?'
                ));
            }

            $statusPath = $outputDir . '/metrica_uploadings.json';
            file_put_contents($statusPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("\nСтатус загрузок: {$statusPath}");
        } else {
            $this->error("Ошибка API: {$response->status()} " . $response->body());
        }

        // 4. Проверяем список целей
        $this->line("\n--- Цели счётчика ---");

        $goalsResp = Http::withHeaders([
            'Authorization' => "OAuth {$token}",
        ])->get("https://api-metrica.yandex.net/management/v1/counter/" . self::COUNTER_ID . "/goals");

        if ($goalsResp->successful()) {
            $goals = $goalsResp->json()['goals'] ?? [];
            $targetGoals = ['payment', 'oplata-max', 'oplata-site', 'podpiska-na-bota', 'podpiska-max'];

            foreach ($goals as $g) {
                $name = $g['name'] ?? '?';
                $id = $g['id'] ?? '?';
                $type = $g['type'] ?? '?';
                $conditions = '';
                if (isset($g['conditions'])) {
                    foreach ($g['conditions'] as $c) {
                        $conditions .= ($c['type'] ?? '') . ':' . ($c['url'] ?? '') . ' ';
                    }
                }

                $mark = in_array($name, $targetGoals) ? ' ⚠️ НАША' : '';
                $this->line("  [{$id}] {$name} (type: {$type}) {$conditions}{$mark}");
            }

            $goalsPath = $outputDir . '/metrica_goals.json';
            file_put_contents($goalsPath, json_encode($goalsResp->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("\nЦели: {$goalsPath}");
        } else {
            $this->error("Ошибка API целей: {$goalsResp->status()} " . $goalsResp->body());
        }

        // 5. Сводка
        $this->line("\n--- Сводка для техподдержки ---");
        $this->line("Counter ID: " . self::COUNTER_ID);
        $this->line("Платежей всего: " . $payments->count());
        $this->line("ClientId строк: " . count($clientIdRows));
        $this->line("UserId строк: " . count($userIdRows));
        $this->line("Период: последние {$hours} часов");
        $this->line("Файлы в: {$outputDir}/metrica_*.{csv,json}");

        return 0;
    }

    private function resolveGoalName(object $payment): string
    {
        if ($payment->messenger === 'max') return 'oplata-max';
        if (in_array($payment->context, ['web', 'public_landing'])) return 'oplata-site';
        return 'payment';
    }

    private function buildCsv(array $header, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }
}