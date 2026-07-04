<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_votes', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('user_id');
            $table->string('device_id', 64)->nullable()->after('ip_address');

            $table->index('ip_address');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('chart_votes', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropIndex(['device_id']);
            $table->dropColumn(['ip_address', 'device_id']);
        });
    }
};
