<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVoice extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'style',
        'voice_id', 'task_id', 'generate_task_id',
        'source_audio_url', 'verify_phrase', 'verify_audio_url',
        'status', 'error_message', 'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function scopeReady($q)
    {
        return $q->where('status', 'ready')->where('is_available', true);
    }

    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }
}