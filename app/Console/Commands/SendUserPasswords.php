<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SendUserPasswords extends Command
{
    protected $signature = 'users:send-passwords 
        {--dry-run : Only show how many users need passwords, don\'t send}
        {--limit=0 : Limit number of users to process (0 = all)}
        {--delay=100 : Delay between messages in ms}
        {--chunk=100 : Process users in chunks of N}';

    protected $description = 'Generate passwords for users without one and send via Telegram';

    public function handle(TelegramNotificationService $telegram)
    {
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $chunkSize = (int) $this->option('chunk');

        $total = User::where('is_blocked', 0)
        //->where('user_id', 288559694);
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->count();

        $this->info("Users without password: {$total}");

        if ($isDryRun || $total === 0) {
            return 0;
        }

        if (!$this->confirm("Send passwords to {$total} users?")) {
            return 0;
        }

        $sent = 0;
        $failed = 0;
        $generated = 0;
        $processed = 0;
        $maxProcess = $limit > 0 ? $limit : $total;

        $this->info("Processing in chunks of {$chunkSize}, limit: " . ($limit > 0 ? $limit : 'all') . "...");

        $shouldStop = false;

        User::where('is_blocked', 0)
        //->where('user_id', 288559694)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->orderBy('user_id')
            ->chunk($chunkSize, function ($users) use (
                $telegram, $delay, &$sent, &$failed, &$generated, &$processed, $maxProcess, &$shouldStop
            ) {
                if ($shouldStop) return false;

                foreach ($users as $user) {
                    if ($processed >= $maxProcess) {
                        $shouldStop = true;
                        return false;
                    }

                    $rawPassword = $this->generateReadablePassword();

                    $user->update([
                        'password' => Hash::make($rawPassword),
                    ]);
                    $generated++;

                    $siteUrl = 'https://narepite.site';
                    $loginId = $user->email ?: (string) $user->user_id;
                    $firstName = $user->first_name ?: 'Привет';

                    $message = "🔐 <b>{$firstName}, если блокировка телеграма тебя уже коснулась, то теперь ты можешь войти на сайт и генерировать треки там!</b>\n\n"
                     . "Мы сделали вход по логину и паролю — на случай, если Telegram недоступен.\n\n"
                     . "🌐 Сайт: {$siteUrl}/login\n"
                     . "👤 Логин: <code>{$loginId}</code>\n"
                     . "🔑 Пароль: <code>{$rawPassword}</code>\n\n"
                     . "💡 Ты можешь сменить пароль в <b>Профиле</b> на сайте.\n\n"
                     . "Telegram-вход тоже работает как раньше 👍\n\n"
                     . "Также мы скоро запустим бота в мессенджере MAX. Следи за новостями на нашем сайте(плашка уведомлений) и в нашем телеграма канале - @na_repite_official\n\n";

                    $result = $telegram->sendMessage($user->user_id, $message);

                    if ($result) {
                        $sent++;
                    } else {
                        $failed++;
                    }

                    $processed++;

                    if ($processed % 50 === 0) {
                        $this->line("  Progress: {$processed}/{$maxProcess} — sent:{$sent} failed:{$failed}");
                    }

                    usleep($delay * 1000);
                }
            });

        $this->newLine();
        $this->info("Done!");
        $this->line("  🔑 Passwords generated: {$generated}");
        $this->line("  ✅ Messages sent: {$sent}");
        $this->line("  ❌ Failed: {$failed}");

        return 0;
    }

    private function generateReadablePassword(): string
    {
        $consonants = 'bdfghjkmnpqrstvwxyz';
        $vowels = 'aeiou';
        $digits = '23456789';

        $password = '';
        for ($i = 0; $i < 3; $i++) {
            $password .= $consonants[random_int(0, strlen($consonants) - 1)];
            $password .= $vowels[random_int(0, strlen($vowels) - 1)];
        }
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];

        return $password;
    }
}