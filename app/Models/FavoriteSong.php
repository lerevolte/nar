<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteSong extends Model
{
    protected $fillable = [
        'user_id',
        'song_id',
        'variant', // 1 или 2
    ];

    protected $casts = [
        'variant' => 'integer',
    ];

    /**
     * Песня
     */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}