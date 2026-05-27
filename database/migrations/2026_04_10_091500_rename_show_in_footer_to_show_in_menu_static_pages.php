<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->renameColumn('show_in_footer', 'show_in_menu');
        });
    }
    public function down(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->renameColumn('show_in_menu', 'show_in_footer');
        });
    }
};