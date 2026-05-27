<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    private const ADMIN_IDS = [288559694, 154483653, 6231501485, 10276713030];

    private function ensureAdmin(Request $request): void
    {
        $user = $request->get('auth_user');
        if (!$user || !in_array($user->user_id, self::ADMIN_IDS)) {
            abort(403);
        }
    }

    // ============================================
    // PUBLIC
    // ============================================

    /**
     * Публичная страница: /pages/{slug} или /pages/{parent_slug}/{slug}
     */
    public function show(Request $request, string $slug, ?string $childSlug = null)
    {
        $authUser = null;
        $token = $request->cookie('tg_session');
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $authUser = $authService->getUserBySessionToken($token);
        }

        if ($childSlug) {
            // Дочерняя страница
            $parent = Page::published()->whereNull('parent_id')->where('slug', $slug)->firstOrFail();
            $page = Page::published()->where('parent_id', $parent->id)->where('slug', $childSlug)->firstOrFail();
        } else {
            // Корневая страница
            $page = Page::published()->whereNull('parent_id')->where('slug', $slug)->firstOrFail();
        }

        // Счётчик просмотров (раз в сессию)
        $viewedKey = 'page_viewed_' . $page->id;
        if (!$request->session()->has($viewedKey)) {
            $page->increment('views_count');
            $request->session()->put($viewedKey, true);
        }

        // Корневая страница + её дочерние для сайдбара
        $rootPage = $page->root_page;
        $siblings = Page::published()
            ->where('parent_id', $rootPage->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('public.pages.show', compact('page', 'rootPage', 'siblings', 'authUser'));
    }

    // ============================================
    // ADMIN
    // ============================================

    public function adminIndex(Request $request)
    {
        $this->ensureAdmin($request);

        // Группируем: корневые + дочерние под ними
        $rootPages = Page::whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['children'])
            ->get();

        return view('admin.pages.index', compact('rootPages'));
    }

    public function adminCreate(Request $request)
    {
        $this->ensureAdmin($request);
        $page = new Page();
        $rootPages = Page::root()->orderBy('title')->get();
        $allArticles = \App\Models\Article::orderBy('title')->get(['id', 'title']);
        $allSongs = \App\Models\ChartEntry::with(['song', 'user'])
            ->select('song_id', 'user_id', \DB::raw('MIN(id) as id'))
            ->groupBy('song_id', 'user_id')
            ->get()
            ->map(function ($entry) {
                $song = $entry->song;
                if (!$song || !$song->file_path) return null;
                return [
                    'id' => $entry->song_id,
                    'title' => $song->title ?? 'Без названия',
                    'cover_url' => $song->cover_url,
                    'audio_url' => $song->file_path,
                    'author' => $entry->user->first_name ?? $entry->user->username ?? 'Автор',
                ];
            })
            ->filter()
            ->unique('id')
            ->values();

        return view('admin.pages.form', compact('page', 'rootPages', 'allArticles', 'allSongs'));
    }

    public function adminEdit(Request $request, $id)
    {
        $this->ensureAdmin($request);
        $page = Page::findOrFail($id);
        $rootPages = Page::root()->where('id', '!=', $id)->orderBy('title')->get();
        $allArticles = \App\Models\Article::orderBy('title')->get(['id', 'title']);
        $allSongs = \App\Models\ChartEntry::with(['song', 'user'])
            ->select('song_id', 'user_id', \DB::raw('MIN(id) as id'))
            ->groupBy('song_id', 'user_id')
            ->get()
            ->map(function ($entry) {
                $song = $entry->song;
                if (!$song || !$song->file_path) return null;
                return [
                    'id' => $entry->song_id,
                    'title' => $song->title ?? 'Без названия',
                    'cover_url' => $song->cover_url,
                    'audio_url' => $song->file_path,
                    'author' => $entry->user->first_name ?? $entry->user->username ?? 'Автор',
                ];
            })
            ->filter()
            ->unique('id')
            ->values();

        return view('admin.pages.form', compact('page', 'rootPages', 'allArticles', 'allSongs'));
    }

    public function adminStore(Request $request, ?int $id = null)
    {
        $this->ensureAdmin($request);

        $user = $request->get('auth_user');
        $page = $id ? Page::findOrFail($id) : new Page();

        $request->validate([
            'title' => 'required|string|max:500',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer|exists:pages,id',
            'excerpt' => 'nullable|string',
            'blocks' => 'nullable|array',
            'blocks.*.type' => 'required|string',
            'blocks.*.data' => 'nullable|array',
            'seo_title' => 'nullable|string|max:500',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'noindex' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'show_in_menu' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $parentId = $request->input('parent_id') ?: null;

        // Нельзя быть родителем самого себя
        if ($parentId && $page->id && $parentId == $page->id) {
            return back()->withInput()->withErrors(['parent_id' => 'Страница не может быть родителем самой себя']);
        }

        // Проверка глубины — только 2 уровня
        if ($parentId) {
            $parent = Page::find($parentId);
            if ($parent && $parent->parent_id) {
                return back()->withInput()->withErrors(['parent_id' => 'Поддерживается только 2 уровня вложенности']);
            }
        }

        // Slug
        $slug = trim($request->input('slug', ''));
        if (empty($slug)) {
            $slug = Page::generateUniqueSlug($request->input('title'), $parentId, $page->id);
        } else {
            $exists = Page::where('parent_id', $parentId)
                ->where('slug', $slug)
                ->when($page->id, fn($q) => $q->where('id', '!=', $page->id))
                ->exists();
            if ($exists) {
                return back()->withInput()->withErrors(['slug' => 'Такой слаг уже существует в этом разделе']);
            }
        }

        $page->fill([
            'parent_id' => $parentId,
            'slug' => $slug,
            'title' => $request->input('title'),
            'excerpt' => $request->input('excerpt'),
            'blocks' => $request->input('blocks') ?: null,
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'seo_keywords' => $request->input('seo_keywords'),
            'og_image' => $request->input('og_image'),
            'canonical_url' => $request->input('canonical_url'),
            'noindex' => (bool) $request->input('noindex'),
            'is_published' => (bool) $request->input('is_published'),
            'show_in_menu' => $request->input('show_in_menu') !== null ? (bool) $request->input('show_in_menu') : true,
            'sort_order' => (int) $request->input('sort_order', 0),
        ]);

        if (!$page->author_id) {
            $page->author_id = $user->user_id;
        }

        $page->save();

        return redirect()->route('admin.pages.edit', $page->id)->with('success', 'Страница сохранена');
    }

    public function adminDestroy(Request $request, int $id)
    {
        $this->ensureAdmin($request);
        Page::findOrFail($id)->delete();
        return redirect()->route('admin.pages.index')->with('success', 'Страница удалена');
    }
}