<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_orders', function (Blueprint $table) {
            $table->string('login_token', 100)->nullable()->index();
            $table->timestamp('login_token_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guest_orders', function (Blueprint $table) {
            $table->dropColumn(['login_token', 'login_token_expires_at']);
        });
    }
};
