<?php

namespace App\Providers;

use App\Models\Page;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            static $menuPages = null;
            static $menuStaticPages = null;

            if ($menuPages === null) {
                try { $menuPages = \App\Models\Page::whereNull('parent_id')
                    ->where('is_published', true)
                    ->where('show_in_menu', true)
                    ->orderBy('sort_order')
                    ->with(['children' => function ($q) {
                        $q->where('is_published', true)->orderBy('sort_order')->orderBy('id');
                    }])
                    ->get(); }
                catch (\Exception $e) { $menuPages = collect(); }
            }
            if ($menuStaticPages === null) {
                try { $menuStaticPages = \App\Models\StaticPage::forMenu()->get(); }
                catch (\Exception $e) { $menuStaticPages = collect(); }
            }

            $view->with('menuPages', $menuPages);
            $view->with('menuStaticPages', $menuStaticPages);
        });
    }

    public function register(): void
    {
        //
    }
}