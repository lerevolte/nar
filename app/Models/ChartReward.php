<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartReward extends Model
{
    protected $fillable = [
        'chart_id',
        'user_id',
        'chart_entry_id',
        'position',
        'songs_reward',
    ];

    protected $casts = [
        'position' => 'integer',
        'songs_reward' => 'integer',
    ];

    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ChartEntry::class, 'chart_entry_id');
    }
}