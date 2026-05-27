<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('static_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('banner_text')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('banner_bg_color', 20)->nullable();
            $table->longText('content_html')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('noindex')->default(false);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_pages');
    }
};