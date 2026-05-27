<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrBatch extends Model
{
    protected $fillable = [
        'token',
        'name',
        'quantity',
        'bonus_songs',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'scans_count',
    ];

    public function codes()
    {
        return $this->hasMany(QrCode::class, 'batch_id');
    }
}