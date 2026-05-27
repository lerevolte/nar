<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    protected $table = 'drafts';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'occasion',
        'occasion_text',
        'genre',
        'genre_text',
        'description',
        'lyrics',
        'song_title',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}