<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_voices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('style', 100)->nullable();
            $table->string('voice_id', 255)->nullable();          // итоговый voiceId от Suno
            $table->string('task_id', 255)->nullable();            // taskId валидации
            $table->string('generate_task_id', 255)->nullable();   // taskId генерации голоса
            $table->string('source_audio_url', 500);               // загруженный юзером файл
            $table->string('verify_phrase', 1000)->nullable();     // фраза для прочтения
            $table->string('verify_audio_url', 500)->nullable();   // аудио с прочтением фразы
            $table->enum('status', ['uploading', 'validating', 'phrase_ready', 'verifying', 'generating', 'ready', 'expired', 'failed'])->default('uploading');
            $table->text('error_message')->nullable();
            $table->boolean('is_available')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_voices');
    }
};