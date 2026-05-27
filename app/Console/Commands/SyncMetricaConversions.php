<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncMetricaConversions extends Command
{
    protected $signature = 'metrica:sync-conversions {--hours=6 : За сколько часов брать платежи} {--dry : Показать без отправки} {--force : Отправить повторно уже отправленные}';
    protected $description = 'Выгрузка успешных платежей в Яндекс.Метрику как офлайн-конверсии';

    private const COUNTER_ID = 105879987;

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dry = $this->option('dry');

        $token = config('services.yandex_metrica.oauth_token');
        if (!$token && !$dry) {
            $this->error('YANDEX_METRICA_OAUTH_TOKEN не задан в конфиге');
            return 1;
        }

        // Берём неотправленные успешные платежи
        $query = DB::table('payments')
            ->where('status', 'succeeded')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('id');

        if (!$this->option('force')) {
            $query->where('metrica_synced', false);
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            $this->info('Нет новых платежей для выгрузки.');
            return 0;
        }

        $this->info("Найдено {$payments->count()} платежей.");

        // Получаем ym_client_id для пользователей
        $userIds = $payments->pluck('user_id')->unique()->toArray();
        $ymClients = DB::table('users')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('ym_client_id')
            ->pluck('ym_client_id', 'user_id')
            ->toArray();

        // Формируем CSV — два массива: для ClientId и для UserId
        $clientIdRows = [];
        $userIdRows = [];

        foreach ($payments as $p) {
            $goalName = $this->resolveGoalName($p);
            $dt = new \DateTime(
                $p->updated_at ?: $p->created_at,
                new \DateTimeZone('America/New_York')
            );
            $timestamp = $dt->getTimestamp();
            $price = (float) $p->amount;

            $ymClientId = $ymClients[$p->user_id] ?? null;

            // Проверяем: реальный _ym_uid — 16-19 цифр
            if ($ymClientId && preg_match('/^\d{16,19}$/', $ymClientId)) {
                $clientIdRows[] = [$ymClientId, $goalName, $timestamp, $price, 'RUB'];
            } else {
                $userIdRows[] = [$p->user_id, $goalName, $timestamp, $price, 'RUB'];
            }
        }

        if ($dry) {
            $this->info("ClientId строк: " . count($clientIdRows));
            $this->info("UserId строк: " . count($userIdRows));
            foreach (array_merge($clientIdRows, $userIdRows) as $row) {
                $this->line(implode(', ', $row));
            }
            return 0;
        }

        $synced = 0;

        // Отправляем пачку ClientId
        if (!empty($clientIdRows)) {
            $csv = $this->buildCsv(['ClientId', 'Target', 'DateTime', 'Price', 'Currency'], $clientIdRows);
            $ok = $this->uploadToMetrica($token, $csv, 'CLIENT_ID');
            if ($ok) {
                $synced += count($clientIdRows);
                $this->info("✅ ClientId: " . count($clientIdRows) . " конверсий отправлено");
            } else {
                $this->error("❌ Ошибка отправки ClientId пачки");
            }
        }

        // Отправляем пачку UserId
        if (!empty($userIdRows)) {
            $csv = $this->buildCsv(['UserId', 'Target', 'DateTime', 'Price', 'Currency'], $userIdRows);
            $ok = $this->uploadToMetrica($token, $csv, 'USER_ID');
            if ($ok) {
                $synced += count($userIdRows);
                $this->info("✅ UserId: " . count($userIdRows) . " конверсий отправлено");
            } else {
                $this->error("❌ Ошибка отправки UserId пачки");
            }
        }

        // Помечаем как отправленные
        if ($synced > 0) {
            DB::table('payments')
                ->whereIn('id', $payments->pluck('id')->toArray())
                ->update(['metrica_synced' => true]);

            $this->info("Помечено {$synced} платежей как отправленные.");
        }

        return 0;
    }

    private function resolveGoalName(object $payment): string
    {
        if ($payment->messenger === 'max') {
            return 'oplata-max';
        }

        // context = 'web' или 'public_landing' → оплата с сайта
        if (in_array($payment->context, ['web', 'public_landing'])) {
            return 'oplata-site';
        }

        // Всё остальное — Telegram
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

    private function uploadToMetrica(string $token, string $csv, string $clientIdType): bool
    {
        $url = "https://api-metrica.yandex.net/management/v1/counter/" . self::COUNTER_ID . "/offline_conversions/upload";

        try {
            $response = Http::withHeaders([
                'Authorization' => "OAuth {$token}",
            ])->attach(
                'file', $csv, 'conversions.csv'
            )->post($url, [
                'client_id_type' => $clientIdType,
                'comment' => 'Laravel sync ' . now()->format('Y-m-d H:i'),
            ]);

            $this->line("API Response: " . $response->body());
            Log::info("Metrica sync response ({$clientIdType}): " . $response->body());

            if ($response->successful()) {
                return true;
            }

            Log::error("Metrica sync FAIL ({$clientIdType}): {$response->status()} " . $response->body());
            $this->error("API ответ: {$response->status()} " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Metrica sync exception: " . $e->getMessage());
            $this->error("Exception: " . $e->getMessage());
            return false;
        }
    }
}