<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartVote extends Model
{
    protected $fillable = [
        'chart_entry_id',
        'user_id',
        'ip_address',
        'device_id',
    ];

    /**
     * Запись чарта
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(ChartEntry::class, 'chart_entry_id');
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
