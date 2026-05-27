<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_id');
            $table->bigInteger('user_id');
            $table->unsignedBigInteger('chart_entry_id');
            $table->integer('position'); // 1, 2, 3
            $table->integer('songs_reward'); // сколько песен начислено
            $table->timestamps();

            $table->index('chart_id');
            $table->index('user_id');
            $table->unique(['chart_id', 'position']); // одна позиция — один приз
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_rewards');
    }
};