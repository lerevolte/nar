<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $table = 'songs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'parent_song_id',
        'source_audio_id',
        'operation_type',
        'model',
        'title',
        'occasion',
        'genre',
        'description',
        'lyrics',
        'file_path',
        'file_path_2',
        'instrumental_path',
        'instrumental_url_1',
        'instrumental_url_2',
        'vocal_url_1',
        'vocal_url_2',
        'suno_task_id',
        'audio_id_1',
        'audio_id_2',
        'video_url',
        'is_deleted',
        'refunded_at',
        'stem_task_id_1',
        'stem_task_id_2',
        'cover_url',
        'plays_count',
        'api_source',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function votes()
    {
        return $this->hasMany(ChartVote::class, 'song_id', 'id');
    }

    public function getVotesCountAttribute()
    {
        return $this->votes()->count();
    }

    public function scopeNotDeleted($query)
    {
        return $query->where(function ($q) {
            $q->where('is_deleted', false)->orWhereNull('is_deleted');
        });
    }
}
