<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = [
        'admin_id',
        'type',
        'channel',
        'status',
        'segment',
        'total_users',
        'sent_count',
        'failed_count',
        'blocked_count',
        'last_user_id',
        'text_content',
        'web_title',
        'web_message',
        'message_id',
        'from_chat_id',
        'video_file_id',
        'caption',
        'started_at',
        'completed_at',
    ];
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $casts = [
        'total_users' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'blocked_count' => 'integer',
        'last_user_id' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function progressPercent(): int
    {
        if ($this->total_users <= 0) return 0;
        $processed = $this->sent_count + $this->failed_count + $this->blocked_count;
        return (int) round($processed / $this->total_users * 100);
    }
}