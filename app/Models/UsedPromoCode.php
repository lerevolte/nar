<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsedPromoCode extends Model
{
    protected $table = 'used_promo_codes';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'promo_code_id',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }
}