<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'parent_id',
        'slug',
        'title',
        'excerpt',
        'blocks',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'canonical_url',
        'noindex',
        'is_published',
        'show_in_menu',
        'sort_order',
        'views_count',
        'author_id',
    ];

    protected $casts = [
        'blocks' => 'array',
        'is_published' => 'boolean',
        'show_in_menu' => 'boolean',
        'noindex' => 'boolean',
        'sort_order' => 'integer',
        'views_count' => 'integer',
        'parent_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Родительская страница
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Дочерние страницы
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Только опубликованные дочерние
     */
    public function publishedChildren(): HasMany
    {
        return $this->children()->where('is_published', true);
    }

    /**
     * Только опубликованные
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Только корневые (без родителя)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Страницы для главного меню (корневые, опубликованные, show_in_menu=true)
     */
    public function scopeForMenu($query)
    {
        return $query->whereNull('parent_id')
            ->where('is_published', true)
            ->where('show_in_menu', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Полный URL страницы
     */
    public function getUrlAttribute(): string
    {
        if ($this->parent_id && $this->parent) {
            return '/pages/' . $this->parent->slug . '/' . $this->slug;
        }
        return '/pages/' . $this->slug;
    }

    /**
     * Корневая страница (для построения сайдбара и хлебных крошек)
     */
    public function getRootPageAttribute(): Page
    {
        return $this->parent_id ? $this->parent : $this;
    }

    /**
     * Генерация уникального слага в рамках родителя
     */
    public static function generateUniqueSlug(string $title, ?int $parentId = null, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        if (empty($base)) {
            $base = 'page-' . time();
        }

        $slug = $base;
        $counter = 1;

        while (
            self::where('parent_id', $parentId)
                ->where('slug', $slug)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Финальный SEO title
     */
    public function getFinalSeoTitleAttribute(): string
    {
        return $this->seo_title ?: $this->title;
    }

    /**
     * Финальное SEO description
     */
    public function getFinalSeoDescriptionAttribute(): string
    {
        return $this->seo_description ?: ($this->excerpt ?: '');
    }
}