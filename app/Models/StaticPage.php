<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StaticPage extends Model
{
    protected $fillable = [
        'slug','title','banner_text','banner_image','banner_bg_color',
        'content_html','seo_title','seo_description','seo_keywords',
        'canonical_url','noindex','is_published','show_in_menu','sort_order',
    ];

    protected $casts = [
        'noindex' => 'boolean',
        'is_published' => 'boolean',
        'show_in_menu' => 'boolean',
    ];

    public function scopePublished($q) { return $q->where('is_published', true); }
    public function scopeForMenu($q) { return $q->where('is_published', true)->where('show_in_menu', true)->orderBy('sort_order'); }

    public function getFinalSeoTitleAttribute() { return $this->seo_title ?: $this->title; }
    public function getFinalSeoDescriptionAttribute() { return $this->seo_description ?: Str::limit(strip_tags($this->content_html), 160); }

    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        if (!$base) $base = 'page-' . time();
        $slug = $base;
        $i = 2;
        while (self::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}