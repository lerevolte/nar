<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPersona extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'style',
        'persona_id', 'task_id', 'audio_id', 'song_id',
        'status', 'error_message',
    ];

    public function scopeReady($q) { return $q->where('status', 'ready'); }
    public function scopeForUser($q, int $userId) { return $q->where('user_id', $userId); }
    public function song() { return $this->belongsTo(Song::class); }
}