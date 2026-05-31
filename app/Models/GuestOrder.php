<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestOrder extends Model
{
    protected $fillable = [
        'token',
        'contact',
        'contact_type',
        'first_name',
        'title',
        'lyrics',
        'genre',
        'artist',
        'vocal_gender',
        'voice_id',
        'language',
        'occasion',
        'description',
        'amount',
        'payment_id',
        'status',
        'user_id',
        'song_id',
        'suno_task_id',
        'ip',
        'user_agent',
        'paid_at',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'ym_client_id',
        'login_token',
        'login_token_expires_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'user_id' => 'integer',
        'song_id' => 'integer',
        'paid_at' => 'datetime',
        'login_token_expires_at' => 'datetime',
    ];

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'generating', 'completed']);
    }
}