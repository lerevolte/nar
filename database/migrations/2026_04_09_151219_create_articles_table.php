<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->string('title', 500);
            $table->text('excerpt')->nullable(); // краткое описание для списка
            $table->longText('content_html')->nullable(); // WYSIWYG контент
            $table->json('blocks')->nullable(); // JSON-блоки (для будущего конструктора)
            $table->string('cover_url', 500)->nullable();

            // SEO
            $table->string('seo_title', 500)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);

            // Meta
            $table->unsignedBigInteger('author_id')->nullable(); // user_id автора
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index('published_at');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};