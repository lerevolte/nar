<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_personas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->string('description', 500);
            $table->string('style', 100)->nullable();
            $table->string('persona_id', 255)->nullable();
            $table->string('task_id', 255)->nullable();
            $table->string('audio_id', 255)->nullable();
            $table->unsignedBigInteger('song_id')->nullable();
            $table->enum('status', ['creating', 'ready', 'failed'])->default('creating');
            $table->string('error_message', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_personas');
    }
};