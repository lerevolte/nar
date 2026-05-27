<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartEntry extends Model
{
    protected $fillable = [
        'chart_id',
        'song_id',
        'user_id',
        'votes_count',
        'position',
        'variant',
        'comment'
    ];

    protected $casts = [
        'votes_count' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Чарт
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    /**
     * Песня
     */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Автор (пользователь)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Голоса
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ChartVote::class);
    }
}