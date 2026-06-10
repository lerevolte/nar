<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $login  Логин (email пользователя)
     * @param  string|null  $password  Пароль в открытом виде (только для нового пользователя)
     * @param  string  $title  Название заказанной песни
     */
    public function __construct(
        public string $login,
        public ?string $password,
        public string $title = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Доступ в личный кабинет — На Репите 🎵',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-credentials',
            with: [
                'login' => $this->login,
                'password' => $this->password,
                'title' => $this->title,
                'cabinetUrl' => rtrim((string) config('app.url'), '/').'/login',
            ],
        );
    }
}
