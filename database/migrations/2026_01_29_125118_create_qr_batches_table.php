<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_batches', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique(); // Токен для доступа к странице
            $table->string('name')->nullable(); // Название партии (для админа)
            $table->integer('quantity'); // Количество QR-кодов
            $table->integer('bonus_songs')->default(1); // Сколько песен начислять
            $table->string('utm_source')->default('qrcode');
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->integer('scans_count')->default(0); // Статистика сканирований
            $table->timestamps();
        });

        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('qr_batches')->onDelete('cascade');
            $table->string('code', 32)->unique(); // Уникальный код для каждого QR
            $table->integer('scans_count')->default(0);
            $table->timestamp('first_scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('qr_batches');
    }
};