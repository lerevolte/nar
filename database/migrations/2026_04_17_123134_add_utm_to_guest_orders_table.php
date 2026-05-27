<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_orders', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('ym_client_id', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guest_orders', function (Blueprint $table) {
            $table->dropColumn(['utm_source','utm_medium','utm_campaign','utm_content','utm_term','ym_client_id']);
        });
    }
};
