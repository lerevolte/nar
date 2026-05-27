<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;

class ArticleController extends Controller
{
    /**
     * Публичный список статей
     */
    public function index(Request $request)
    {
        // Проверяем авторизацию (опционально, для хедера)
        $authUser = null;
        $token = $request->cookie('tg_session');
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $authUser = $authService->getUserBySessionToken($token);
        }

        $articles = Article::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('public.articles.index', compact('articles', 'authUser'));
    }

    /**
     * Публичная страница отдельной статьи (каркас — доработаем позже)
     */
    public function show(Request $request, string $slug)
    {
        $authUser = null;
        $token = $request->cookie('tg_session');
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $authUser = $authService->getUserBySessionToken($token);
        }

        $article = Article::published()->where('slug', $slug)->firstOrFail();

        // Счётчик просмотров (раз в сессию)
        $viewedKey = 'article_viewed_' . $article->id;
        if (!$request->session()->has($viewedKey)) {
            $article->increment('views_count');
            $request->session()->put($viewedKey, true);
        }

 
        $plainText = trim(strip_tags($article->content_html ?? ''));

        // Добавим текст из blocks (если он там есть)
        if (is_array($article->blocks ?? null)) {
            foreach ($article->blocks as $block) {
                if (!empty($block['data']['html'])) {
                    $plainText .= ' ' . strip_tags($block['data']['html']);
                }
            }
        }

        // preg_match_all работает с любыми алфавитами
        preg_match_all('/\S+/u', $plainText, $matches);
        $wordCount = count($matches[0] ?? []);

        // Средняя скорость чтения на русском ~180-200 слов/мин
        $readingTime = max(1, (int) ceil($wordCount / 190));

        // Связанные статьи
        $relatedArticles = collect();
        if (!empty($article->related_ids)) {
            $relatedArticles = Article::published()
                ->whereIn('id', $article->related_ids)
                ->get()
                ->sortBy(fn($a) => array_search($a->id, $article->related_ids))
                ->values();
        }

        return view('public.articles.show', compact('article', 'authUser', 'readingTime', 'relatedArticles'));
    }

    // ============================================
    // ADMIN
    // ============================================

    private const ADMIN_IDS = [288559694, 154483653, 6231501485, 10276713030];

    private function ensureAdmin(Request $request): void
    {
        $user = $request->get('auth_user');
        if (!$user || !in_array($user->user_id, self::ADMIN_IDS)) {
            abort(403);
        }
    }

    /**
     * Админ: список статей
     */
    public function adminIndex(Request $request)
    {
        $this->ensureAdmin($request);

        $articles = Article::orderByDesc('created_at')->paginate(30);

        return view('admin.articles.index', compact('articles'));
    }

    public function adminCreate(Request $request)
    {
        $this->ensureAdmin($request);
        $article = new Article();
        $allArticles = Article::orderBy('title')->get(['id', 'title']);
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

        return view('admin.articles.form', compact('article', 'allArticles', 'allSongs'));
    }

    public function adminEdit(Request $request, $id)
    {
        $this->ensureAdmin($request);
        $article = Article::findOrFail($id);
        $allArticles = Article::where('id', '!=', $id)->orderBy('title')->get(['id', 'title']);
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

        return view('admin.articles.form', compact('article', 'allArticles', 'allSongs'));
    }

    /**
     * Админ: сохранение (создание или обновление)
     */
    public function adminStore(Request $request, ?int $id = null)
    {
        $this->ensureAdmin($request);

        $user = $request->get('auth_user');
        $article = $id ? Article::findOrFail($id) : new Article();

        $request->validate([
            'title' => 'required|string|max:500',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string',
            'content_html' => 'nullable|string',
            'cover_url' => 'nullable|string|max:500',
            'banner_url' => 'nullable|string|max:500',
            'reading_time' => 'nullable|integer|min:1|max:999',
            'related_ids' => 'nullable|array',
            'related_ids.*' => 'integer',
            'seo_title' => 'nullable|string|max:500',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'noindex' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'is_guide' => 'nullable|boolean',
            'blocks' => 'nullable|array',
            'blocks.*.type' => 'required|string',
            'blocks.*.data' => 'nullable|array',
            'cover_thumb_url' => 'nullable|string|max:500',
        ]);

        // Slug
        $slug = trim($request->input('slug', ''));
        if (empty($slug)) {
            $slug = Article::generateUniqueSlug($request->input('title'), $article->id);
        } else {
            $exists = Article::where('slug', $slug)
                ->when($article->id, fn($q) => $q->where('id', '!=', $article->id))
                ->exists();
            if ($exists) {
                return back()->withInput()->withErrors(['slug' => 'Такой слаг уже существует']);
            }
        }

        $isPublished = (bool) $request->input('is_published');

        // Related IDs — исключаем саму статью
        $relatedIds = array_filter(
            $request->input('related_ids', []),
            fn($rid) => $article->id ? (int)$rid !== (int)$article->id : true
        );
        $relatedIds = array_values(array_map('intval', $relatedIds));

        $article->fill([
            'slug' => $slug,
            'title' => $request->input('title'),
            'excerpt' => $request->input('excerpt'),
            'content_html' => $request->input('content_html'),
            'cover_url' => $request->input('cover_url'),
            'banner_url' => $request->input('banner_url'),
            'reading_time' => $request->input('reading_time') ?: null,
            'related_ids' => !empty($relatedIds) ? $relatedIds : null,
            'seo_title' => $request->input('seo_title'),
            'seo_description' => $request->input('seo_description'),
            'seo_keywords' => $request->input('seo_keywords'),
            'og_image' => $request->input('og_image'),
            'canonical_url' => $request->input('canonical_url'),
            'noindex' => (bool) $request->input('noindex'),
            'is_published' => $isPublished,
            'is_guide' => (bool) $request->input('is_guide'),
            'blocks' => $request->input('blocks') ?: null,
        ]);

        if (!$article->author_id) {
            $article->author_id = $user->user_id;
        }

        if ($isPublished && !$article->published_at) {
            $article->published_at = now();
        }

        $article->save();

        return redirect()->route('admin.articles.edit', $article->id)
            ->with('success', 'Статья сохранена');
    }

    /**
     * Админ: загрузка изображения (обложка / баннер)
     */
    public function adminUploadImage(Request $request, ImageUploadService $uploader)
    {
        $this->ensureAdmin($request);

        $request->validate([
            'image' => 'required|image|max:10240',
            'type' => 'nullable|string|in:cover,banner,content',
        ]);

        $type = $request->input('type', 'content');

        // Разные размеры под разные задачи
        $sizesMap = [
            'cover'   => ['main' => 1300, 'thumb' => 400],
            'banner'  => ['main' => 1920],
            'content' => ['main' => 1300],
        ];
        $sizes = $sizesMap[$type] ?? $sizesMap['content'];

        $urls = $uploader->upload($request->file('image'), 'articles', $sizes, 82);

        return response()->json([
            'success' => true,
            'url' => $urls['main'],
            'thumb_url' => $urls['thumb'] ?? null,
        ]);
    }

    /**
     * Админ: удаление
     */
    public function adminDestroy(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        Article::findOrFail($id)->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Статья удалена');
    }
}