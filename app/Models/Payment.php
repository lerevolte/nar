<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'payment_id',
        'songs_count',
        'amount',
        'status',
        'context',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}