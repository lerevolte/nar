<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE user_personas MODIFY COLUMN status ENUM('creating','ready','failed','expired') DEFAULT 'creating'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_personas MODIFY COLUMN status ENUM('creating','ready','failed') DEFAULT 'creating'");
    }
};