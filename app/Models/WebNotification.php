<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'broadcast_id',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class);
    }
}