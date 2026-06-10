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
     */
    public function __construct(
        public string $title,
        public ?string $retryUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Не получилось сгенерировать песню — возврат на баланс',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.song-failed',
            with: [
                'title' => $this->title,
                'retryUrl' => $this->retryUrl,
                'cabinetUrl' => rtrim((string) config('app.url'), '/').'/login',
            ],
        );
    }
}
