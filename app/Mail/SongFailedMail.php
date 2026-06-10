<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SongFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $title  Название песни
     * @param  string|null  $retryUrl  Ссылка для повторной генерации (если есть)
     * @param  string|null  $login  Логин (email) для входа в ЛК
     * @param  string|null  $password  Пароль в открытом виде (только если аккаунт только что создан)
     */
    public function __construct(
        public string $title,
        public ?string $retryUrl = null,
        public ?string $login = null,
        public ?string $password = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Не получилось сгенерировать песню — возврат на баланс',
        );
    }

    public function content(): Content
    {
        $base = rtrim((string) config('app.url'), '/');

        return new Content(
            view: 'emails.song-failed',
            with: [
                'title' => $this->title,
                'retryUrl' => $this->retryUrl,
                'login' => $this->login,
                'password' => $this->password,
                'loginUrl' => $base.'/login',
                'resetUrl' => $base.'/forgot-password',
            ],
        );
    }
}
