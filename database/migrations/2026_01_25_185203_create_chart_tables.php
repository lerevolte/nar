<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица чартов (недельных/месячных)
        Schema::create('charts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('period', ['daily', 'weekly', 'monthly', 'all_time']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // Песни, участвующие в чартах
        Schema::create('chart_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_id')->constrained('charts')->onDelete('cascade');
            $table->integer('song_id'); // int(11) signed — как в songs.id
            $table->bigInteger('user_id'); // bigint(20) signed — как в users.user_id
            $table->integer('votes_count')->default(0);
            $table->integer('position')->nullable();
            $table->timestamps();

            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            
            $table->unique(['chart_id', 'song_id']);
        });

        // Голоса пользователей
        Schema::create('chart_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_entry_id')->constrained('chart_entries')->onDelete('cascade');
            $table->bigInteger('user_id'); // bigint(20) signed
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            
            $table->unique(['chart_entry_id', 'user_id']);
        });

        // Сессии для Telegram авторизации
        Schema::create('telegram_sessions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id'); // bigint(20) signed
            $table->string('session_token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_votes');
        Schema::dropIfExists('chart_entries');
        Schema::dropIfExists('charts');
        Schema::dropIfExists('telegram_sessions');
    }
};