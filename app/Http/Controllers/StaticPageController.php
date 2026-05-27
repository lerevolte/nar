<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;

class StaticPageController extends Controller
{
    private function getAuthUser(Request $request)
    {
        $token = $request->cookie('tg_session');
        if (!$token) return null;
        return app(TelegramAuthService::class)->getUserBySessionToken($token);
    }

    private function ensureAdmin(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !in_array($user->user_id, [288559694, 154483653, 6231501485, 10276713030])) {
            abort(403);
        }
        return $user;
    }

    // ===== PUBLIC =====
    public function show(Request $request, string $slug)
    {
        $page = StaticPage::published()->where('slug', $slug)->firstOrFail();
        $page->increment('views_count');
        $authUser = $this->getAuthUser($request);

        return view('public.static-pages.show', compact('page', 'authUser'));
    }

    // ===== ADMIN =====
    public function adminIndex(Request $request)
    {
        $this->ensureAdmin($request);
        $pages = StaticPage::orderBy('sort_order')->orderBy('title')->paginate(30);
        return view('admin.static-pages.index', compact('pages'));
    }

    public function adminCreate(Request $request)
    {
        $this->ensureAdmin($request);
        $page = new StaticPage();
        return view('admin.static-pages.form', compact('page'));
    }

    public function adminEdit(Request $request, $id)
    {
        $this->ensureAdmin($request);
        $page = StaticPage::findOrFail($id);
        return view('admin.static-pages.form', compact('page'));
    }

    public function adminStore(Request $request, $id = null)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'banner_text' => 'nullable|string|max:500',
            'banner_image' => 'nullable|string|max:500',
            'banner_bg_color' => 'nullable|string|max:20',
            'content_html' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:500',
            'noindex' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'show_in_menu' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $page = $id ? StaticPage::findOrFail($id) : new StaticPage();

        $slug = $validated['slug'] ?: StaticPage::generateUniqueSlug($validated['title'], $id);
        $validated['slug'] = $slug;
        $validated['noindex'] = $request->boolean('noindex');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['show_in_menu'] = $request->boolean('show_in_menu');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $page->fill($validated)->save();

        return redirect()->route('admin.static-pages.index')->with('success', 'Страница сохранена');
    }

    public function adminDestroy(Request $request, $id)
    {
        $this->ensureAdmin($request);
        StaticPage::findOrFail($id)->delete();
        return redirect()->route('admin.static-pages.index')->with('success', 'Страница удалена');
    }

    public function adminUploadImage(Request $request, ImageUploadService $uploader)
    {
        $this->ensureAdmin($request);

        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        // Для баннера шириной 1600 достаточно
        $urls = $uploader->upload(
            $request->file('image'),
            'static-pages',
            ['main' => 1600],
            82
        );

        return response()->json([
            'success' => true,
            'url' => $urls['main'],
        ]);
    }
}