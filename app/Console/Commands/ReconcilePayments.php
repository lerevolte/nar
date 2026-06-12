<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\YooKassaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
        {--days=7 : За сколько дней брать платежи}
        {--min-age=5 : Минимальный возраст платежа в минутах (свежие не трогаем — пользователь ещё платит)}
        {--include-expired : Также перепроверить платежи в статусе expired}
        {--dry : Показать без изменений}';

    protected $description = 'Сверка платежей сайта (context=web) с ЮKassa: начисление успешных, отметка отменённых';

    public function handle(YooKassaService $yooKassa): int
    {
        $days = (int) $this->option('days');
        $minAge = (int) $this->option('min-age');
        $dry = $this->option('dry');

        $statuses = ['pending'];
        if ($this->option('include-expired')) {
            $statuses[] = 'expired';
        }

        // Только платежи сайта: платежи бота созданы в другом магазине ЮKassa,
        // их сверяет сам бот — наш API-ключ их не видит (not_found)
        $payments = Payment::whereIn('status', $statuses)
            ->where('context', 'web')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('created_at', '<=', now()->subMinutes($minAge))
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            $this->info('Нет платежей для сверки.');

            return 0;
        }

        $this->info("Найдено {$payments->count()} платежей для сверки.");

        $credited = 0;
        $canceled = 0;
        $skipped = 0;

        foreach ($payments as $payment) {
            $check = $yooKassa->checkPayment($payment->payment_id);
            $actualStatus = $check['status'] ?? 'error';

            $this->line("#{$payment->id} {$payment->payment_id} [{$payment->status}] → ЮKassa: {$actualStatus}");

            if ($actualStatus === 'succeeded') {
                if ($dry) {
                    $this->warn("  → начислить {$payment->songs_count} песен юзеру {$payment->user_id} (dry)");
                } elseif ($yooKassa->processSuccessfulPayment($payment->payment_id)) {
                    $this->info("  → начислено {$payment->songs_count} песен юзеру {$payment->user_id}");
                    Log::info('Reconcile: payment credited', [
                        'payment_id' => $payment->payment_id,
                        'user_id' => $payment->user_id,
                        'songs_count' => $payment->songs_count,
                        'previous_status' => $payment->status,
                    ]);
                } else {
                    $this->error('  → ошибка начисления, см. лог');
                }
                $credited++;
            } elseif ($actualStatus === 'canceled') {
                if (! $dry) {
                    $payment->update(['status' => 'canceled']);
                }
                $canceled++;
            } else {
                // pending / waiting_for_capture / error — не трогаем, проверим в следующий раз
                $skipped++;
            }

            usleep(200_000); // не долбим API ЮKassa
        }

        $this->info("Готово: успешных {$credited}, отменённых {$canceled}, пропущено {$skipped}.".($dry ? ' (dry-run)' : ''));

        return 0;
    }
}
