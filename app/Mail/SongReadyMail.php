<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SongReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public ?string $filePath,
        public ?string $filePath2 = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Твоя песня готова! 🎉 — На Репите',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.song-ready',
            with: [
                'title' => $this->title,
                'filePath' => $this->filePath,
                'filePath2' => $this->filePath2,
                'cabinetUrl' => rtrim((string) config('app.url'), '/').'/login',
            ],
        );
    }
}
