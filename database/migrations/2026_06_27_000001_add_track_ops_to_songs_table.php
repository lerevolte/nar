<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            // Родословная производных треков (продление, кавер, замена фрагмента и т.д.)
            $table->unsignedBigInteger('parent_song_id')->nullable()->after('user_id');
            // audioId исходного клипа в системе провайдера (для extend / replace-section)
            $table->string('source_audio_id')->nullable()->after('parent_song_id');
            // Тип операции, создавшей этот трек
            $table->string('operation_type')->default('generate')->after('source_audio_id');
            // Модель генерации (важно для extend — должна совпадать с источником)
            $table->string('model')->nullable()->after('operation_type');

            $table->index('parent_song_id');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropIndex(['parent_song_id']);
            $table->dropColumn(['parent_song_id', 'source_audio_id', 'operation_type', 'model']);
        });
    }
};
