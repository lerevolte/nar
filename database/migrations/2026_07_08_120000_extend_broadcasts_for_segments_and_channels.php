<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблицы broadcasts / web_notifications были созданы вне Laravel (ботом),
 * поэтому эта миграция только БЕЗОПАСНО расширяет существующие колонки:
 *  - broadcasts.channel  — теперь хранит список каналов через запятую (telegram,max,web)
 *  - broadcasts.segment  — новые ключи сегментов ("no_create", "draft", ...)
 * Расширение VARCHAR — неразрушающая операция.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('broadcasts')) {
            return;
        }

        Schema::table('broadcasts', function (Blueprint $table) {
            if (Schema::hasColumn('broadcasts', 'channel')) {
                $table->string('channel', 64)->default('telegram')->change();
            }
            if (Schema::hasColumn('broadcasts', 'segment')) {
                $table->string('segment', 64)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Сужать обратно не нужно — оставляем расширенные колонки.
    }
};
