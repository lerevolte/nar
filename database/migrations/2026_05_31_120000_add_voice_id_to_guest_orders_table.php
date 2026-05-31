<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_orders', function (Blueprint $table) {
            // Kie voice_id выбранного «своего голоса» (разово, без привязки к аккаунту)
            $table->string('voice_id')->nullable()->after('vocal_gender');
        });
    }

    public function down(): void
    {
        Schema::table('guest_orders', function (Blueprint $table) {
            $table->dropColumn('voice_id');
        });
    }
};
