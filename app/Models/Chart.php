<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chart extends Model
{
    protected $fillable = [
            'name',
            'slug',
            'period',
            'theme',
            'description',
            'cover_emoji',
            'is_active',
            'starts_at',
            'ends_at',
        ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Записи чарта
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ChartEntry::class);
    }

    /**
     * Награды чарта
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ChartReward::class);
    }

    /**
     * Топ записей
     */
    public function topEntries(int $limit = 10)
    {
        return $this->entries()
            ->with(['song', 'user'])
            ->orderByDesc('votes_count')
            ->take($limit)
            ->get();
    }

    /**
     * Проверить, истёк ли чарт
     */
    public function isExpired(): bool
    {
        return now()->gt($this->ends_at);
    }

    /**
     * Проверить, выданы ли награды
     */
    public function hasRewards(): bool
    {
        return $this->rewards()->exists();
    }
}