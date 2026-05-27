<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSession extends Model
{
    protected $table = 'telegram_sessions';

    protected $fillable = [
        'user_id',
        'session_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}