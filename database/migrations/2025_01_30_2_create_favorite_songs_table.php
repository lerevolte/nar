<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_songs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id'); // telegram user_id
            $table->unsignedBigInteger('song_id');
            $table->tinyInteger('variant')->default(1); // 1 или 2 (вариант песни)
            $table->timestamps();

            $table->unique(['user_id', 'song_id', 'variant']);
            $table->index('user_id');
            $table->index('song_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_songs');
    }
};