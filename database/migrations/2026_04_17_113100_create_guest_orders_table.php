<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guest_orders', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique()->index(); // уникальный id заказа (для URL)
            $table->string('contact', 255); // email или телефон (нормализованный)
            $table->enum('contact_type', ['email', 'phone']);
            $table->string('first_name', 100)->nullable();

            // Данные для генерации
            $table->string('title', 255);
            $table->text('lyrics');
            $table->string('genre', 255);
            $table->string('artist', 255)->nullable();
            $table->string('vocal_gender', 10)->nullable(); // m/f/duet/random
            $table->string('language', 5)->default('ru');
            $table->string('occasion', 500)->nullable();
            $table->text('description')->nullable();

            // Платёж
            $table->integer('amount'); // цена в рублях
            $table->string('payment_id', 100)->nullable()->index(); // ID от ЮKassa
            $table->enum('status', [
                'pending_payment',  // платёж создан, ждём оплату
                'paid',             // оплачен, готов к генерации
                'generating',       // идёт генерация
                'completed',        // песня готова
                'failed',           // ошибка
                'cancelled',        // отменён
            ])->default('pending_payment')->index();

            // Привязка после оплаты
            $table->unsignedBigInteger('user_id')->nullable()->index(); // создастся после оплаты
            $table->unsignedBigInteger('song_id')->nullable(); // создастся при генерации
            $table->string('suno_task_id', 100)->nullable();

            // Meta
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_orders');
    }
};
