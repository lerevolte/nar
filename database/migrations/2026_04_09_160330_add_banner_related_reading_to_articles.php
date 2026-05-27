<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('banner_url', 500)->nullable()->after('cover_url');
            $table->unsignedSmallInteger('reading_time')->nullable()->after('views_count'); // минут
            $table->json('related_ids')->nullable()->after('blocks'); // IDs связанных статей
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['banner_url', 'reading_time', 'related_ids']);
        });
    }
};