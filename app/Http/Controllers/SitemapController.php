<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Page;
use App\Models\StaticPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];
        $base = rtrim(config('app.url'), '/');

        // Главная (было /home, теперь /)
        $urls[] = [
            'loc' => $base.'/',
            'changefreq' => 'daily',
            'priority' => '1.0',
            'lastmod' => now()->toAtomString(),
        ];

        // Листинг статей
        $urls[] = [
            'loc' => $base.'/articles',
            'changefreq' => 'daily',
            'priority' => '0.8',
            'lastmod' => now()->toAtomString(),
        ];

        // Статьи
        Article::published()
            ->whereNull('canonical_url')
            ->where('noindex', false)
            ->select('slug', 'updated_at', 'published_at')
            ->orderBy('published_at', 'desc')
            ->chunk(500, function ($articles) use (&$urls, $base) {
                foreach ($articles as $a) {
                    $urls[] = [
                        'loc' => $base.'/articles/'.$a->slug,
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                        'lastmod' => ($a->updated_at ?? $a->published_at)?->toAtomString(),
                    ];
                }
            });

        // Иерархические страницы (Page)
        Page::published()
            ->where('noindex', false)
            ->with('parent')
            ->select('id', 'slug', 'parent_id', 'updated_at')
            ->chunk(500, function ($pages) use (&$urls, $base) {
                foreach ($pages as $p) {
                    if ($p->parent_id && $p->parent) {
                        $loc = $base.'/pages/'.$p->parent->slug.'/'.$p->slug;
                    } else {
                        $loc = $base.'/pages/'.$p->slug;
                    }
                    $urls[] = [
                        'loc' => $loc,
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                        'lastmod' => $p->updated_at?->toAtomString(),
                    ];
                }
            });

        // Статические страницы
        StaticPage::published()
            ->where('noindex', false)
            ->select('slug', 'updated_at')
            ->chunk(500, function ($pages) use (&$urls, $base) {
                foreach ($pages as $p) {
                    // Статические страницы доступны по корневому catch-all маршруту /{slug}
                    $urls[] = [
                        'loc' => $base.'/'.$p->slug,
                        'changefreq' => 'monthly',
                        'priority' => '0.5',
                        'lastmod' => $p->updated_at?->toAtomString(),
                    ];
                }
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
