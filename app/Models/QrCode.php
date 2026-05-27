<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    protected $fillable = [
        'batch_id',
        'code',
        'scans_count',
        'first_scanned_at',
    ];

    protected $casts = [
        'first_scanned_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(QrBatch::class, 'batch_id');
    }

    /**
     * Генерация ссылки для Telegram
     */
    public function getTelegramUrl(): string
    {
        $batch = $this->batch;
        
        // Формат: utm_source_utm_medium_utm_campaign_utm_content_bonusSongs_qrCode
        $parts = [
            $batch->utm_source ?: 'qrcode',
            $batch->utm_medium ?: 'print',
            $batch->utm_campaign ?: 'qr',
            $batch->utm_content ?: $batch->id,
            $batch->bonus_songs,
            $this->code,
        ];
        
        $startParam = implode('_', $parts);
        
        return "tg://resolve?domain=na_repitebot&start={$startParam}";
    }
}