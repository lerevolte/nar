<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $table = 'promo_codes';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'type',
        'value',
        'songs_amount',
        'songs_count',
        'max_uses',
        'current_uses',
        'is_active',
    ];

    protected $casts = [
        'value' => 'integer',
        'songs_amount' => 'integer',
        'songs_count' => 'integer',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Промокод активен и не исчерпан
     */
    public function isValid(): bool
    {
        return $this->is_active && $this->current_uses < $this->max_uses;
    }

    /**
     * Использования промокода
     */
    public function usages()
    {
        return $this->hasMany(UsedPromoCode::class, 'promo_code_id');
    }

    /**
     * Проверить, использовал ли пользователь этот промокод
     */
    public function isUsedByUser(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)->exists();
    }
}