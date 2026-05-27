<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('slug', 255);
            $table->string('title', 500);
            $table->text('excerpt')->nullable();
            $table->json('blocks')->nullable();

            // SEO
            $table->string('seo_title', 500)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);

            // Meta
            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_menu')->default(true); // показывать в главном меню (для корневых)
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('is_published');
            $table->index('sort_order');

            // Уникальность слага в рамках родителя (два корневых не могут иметь одинаковый slug, но родитель+ребёнок могут)
            $table->unique(['parent_id', 'slug']);

            $table->foreign('parent_id')->references('id')->on('pages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};