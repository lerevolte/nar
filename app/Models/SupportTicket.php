<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'email', 'message', 'attached_files', 'status',
        'admin_reply', 'user_id', 'ip',
    ];
}