<?php

namespace App\Http\Middleware;

use App\Models\Article;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO-нормализация URL одним прямым 301-редиректом (без цепочек):
 *  1. Единое зеркало: www.narepite.com → narepite.com
 *  2. Завершающий слеш: /articles/ → /articles (кроме главной)
 *  3. Верхний регистр: /Help → /help
 *  4. Старый раздел /blog/ → актуальные статьи в /articles/
 *  5. Точечные редиректы конкретных URL (опечатки, переименования)
 */
class SeoRedirect
{
    /** Прямые редиректы «старый путь → новый путь» (после нормализации слеша/регистра). */
    private const REDIRECT_MAP = [
        '/articles/prompty-dlya-personalnoy-pesni' => '/articles/prompty-dlya-sozdaniya-personalnoy-pesni',
        '/privcay' => '/privacy',
    ];

    /** Префиксы путей, где регистр менять нельзя (токены, файлы, пользовательский контент). */
    private const CASE_SENSITIVE_PREFIXES = [
        'qr/', 'dl/', 'reset-password/', 'api/',
        'storage/', 'music/', 'uploads/', 'covers/', 'img/', 'css/', 'js/',
    ];

    /** Карта редиректов со старых URL /blog/{slug} на слаги статей в /articles/. */
    private const BLOG_MAP = [
        'kak-sdelat-pozdravlenie-s-dnem-rozdeniia-v-vide-pesni-cerez-neiroset' => 'kak-sdelat-pozdravlenie-s-dnyom-rozhdeniya-cherez-neyroset',
        'kak-sgenerirovat-detskuiu-pesniu-s-pomoshhiu-neiroseti' => 'kak-sgenerirovat-detskuiu-pesniu-s-pomoshhiu-neiroseti',
        'neironka-dlia-generacii-pesen-cto-eto-i-kak-rabotaet' => 'neyronka-dlya-generatsii-pesen',
        'kak-sozdat-rep-ili-xip-xop-s-pomoshhiu-neiroseti-posagovyi-gaid' => 'kak-sozdat-rep-ili-hip-hop-s-pomoschyu-neyroseti',
        'muzyku-pod-nastroenie-ot-neiroseti-kak-ukazat-zanr-i-stil' => 'muzyku-pod-nastroenie-ot-neyroseti',
        'kak-muzykantu-ispolzovat-ii-dlia-zapisi-demo-treka' => 'kak-muzykantu-ispolzovat-ii',
        'kak-sdelat-pesniu-iz-svoego-stixa-instrukciia-dlia-novickov' => 'kak-sdelat-pesnyu-iz-svoego-stiha',
        'generaciia-pesni-po-tekstu-posagovyi-gaid' => 'generatsiya-pesni-po-tekstu-poshagovyy-gayd',
        'bot-dlia-generacii-pesen-v-telegram-kak-polzovatsia' => 'bot-dlya-generatsii-pesen-v-telegram',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $path = $request->getPathInfo();

        // 1. www → без www
        $targetHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        // 2. Завершающий слеш (кроме главной)
        $targetPath = $path !== '/' && str_ends_with($path, '/')
            ? rtrim($path, '/')
            : $path;

        // 3. Верхний регистр → нижний
        if (preg_match('/[A-Z]/', $targetPath) && ! $this->isCaseSensitivePath($targetPath)) {
            $targetPath = strtolower($targetPath);
        }

        // 4. Старый раздел /blog/ → /articles/
        if ($targetPath === '/blog' || str_starts_with($targetPath, '/blog/')) {
            $targetPath = $this->blogTarget($targetPath);
        }

        // 5. Точечные редиректы конкретных URL
        if (isset(self::REDIRECT_MAP[$targetPath])) {
            $targetPath = self::REDIRECT_MAP[$targetPath];
        }

        if ($targetHost === $host && $targetPath === $path) {
            return $next($request);
        }

        $scheme = str_starts_with(config('app.url'), 'https://') ? 'https' : $request->getScheme();
        $port = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':'.$port;
        $query = $request->getQueryString();

        $url = $scheme.'://'.$targetHost.$portSuffix.$targetPath.($query ? '?'.$query : '');

        return new RedirectResponse($url, 301);
    }

    private function isCaseSensitivePath(string $path): bool
    {
        $trimmed = ltrim($path, '/');
        foreach (self::CASE_SENSITIVE_PREFIXES as $prefix) {
            if (str_starts_with($trimmed, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function blogTarget(string $path): string
    {
        $slug = trim(substr($path, strlen('/blog')), '/');

        if ($slug === '') {
            return '/articles';
        }

        if (isset(self::BLOG_MAP[$slug])) {
            return '/articles/'.self::BLOG_MAP[$slug];
        }

        // Статья с тем же slug перенесена в /articles/ без переименования
        try {
            if (Article::published()->where('slug', $slug)->exists()) {
                return '/articles/'.$slug;
            }
        } catch (\Exception $e) {
            // БД недоступна — не блокируем редирект на листинг
        }

        // Аналога нет — на листинг статей, чтобы не отдавать 404 из индекса
        return '/articles';
    }
}
