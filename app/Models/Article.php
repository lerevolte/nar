<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content_html',
        'blocks',
        'cover_url',
        'banner_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'canonical_url',
        'noindex',
        'author_id',
        'is_published',
        'views_count',
        'reading_time',
        'related_ids',
        'published_at',
    ];

    protected $casts = [
        'blocks' => 'array',
        'related_ids' => 'array',
        'is_published' => 'boolean',
        'noindex' => 'boolean',
        'views_count' => 'integer',
        'reading_time' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Автор статьи
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    /**
     * Только опубликованные
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Генерация уникального слага
     */
    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        if (empty($base)) {
            $base = 'article-' . time();
        }

        $slug = $base;
        $counter = 1;

        while (
            self::where('slug', $slug)
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
        return $this->seo_description ?: ($this->excerpt ?: Str::limit(strip_tags($this->content_html ?? ''), 160));
    }
}