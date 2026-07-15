<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Тесты SeoRedirect работают без миграций: middleware не требует БД
 * (обращение к articles для /blog/* обёрнуто в try/catch).
 */
class SeoRedirectTest extends TestCase
{
    public function test_www_host_redirects_to_non_www(): void
    {
        $response = $this->get('http://www.narepite.com/help');

        $response->assertStatus(301);
        $this->assertStringNotContainsString('//www.', $response->headers->get('Location'));
    }

    public function test_trailing_slash_redirects_without_slash(): void
    {
        // Тестовый HTTP-клиент Laravel сам обрезает завершающий слеш (prepareUrlForRequest),
        // поэтому middleware вызывается напрямую
        $request = \Illuminate\Http\Request::create('http://narepite.com/articles/');
        $response = (new \App\Http\Middleware\SeoRedirect)->handle($request, fn () => response('ok'));

        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringEndsWith('/articles', $response->headers->get('Location'));
    }

    public function test_homepage_trailing_slash_is_not_redirected(): void
    {
        $response = $this->get('/');

        $this->assertNotSame(301, $response->getStatusCode());
    }

    public function test_uppercase_path_redirects_to_lowercase(): void
    {
        $response = $this->get('/Help');

        $response->assertStatus(301);
        $this->assertStringEndsWith('/help', $response->headers->get('Location'));
    }

    public function test_case_sensitive_prefixes_are_not_lowercased(): void
    {
        $response = $this->get('/qr/AbCdEf123');

        $this->assertNotSame(301, $response->getStatusCode());
    }

    public function test_query_string_is_preserved(): void
    {
        $response = $this->get('/Articles?page=2');

        $response->assertStatus(301);
        $this->assertStringEndsWith('/articles?page=2', $response->headers->get('Location'));
    }

    public function test_blog_root_redirects_to_articles(): void
    {
        $response = $this->get('/blog');

        $response->assertStatus(301);
        $this->assertStringEndsWith('/articles', $response->headers->get('Location'));
    }

    public function test_blog_slug_from_map_redirects_to_article(): void
    {
        $response = $this->get('/blog/generaciia-pesni-po-tekstu-posagovyi-gaid');

        $response->assertStatus(301);
        $this->assertStringEndsWith(
            '/articles/generatsiya-pesni-po-tekstu-poshagovyy-gayd',
            $response->headers->get('Location')
        );
    }

    public function test_www_blog_slash_resolves_in_single_redirect(): void
    {
        $response = $this->get('http://www.narepite.com/blog/generaciia-pesni-po-tekstu-posagovyi-gaid/');

        $response->assertStatus(301);
        $location = $response->headers->get('Location');
        $this->assertStringNotContainsString('//www.', $location);
        $this->assertStringEndsWith('/articles/generatsiya-pesni-po-tekstu-poshagovyy-gayd', $location);
    }

    public function test_unknown_blog_slug_redirects_to_articles_listing(): void
    {
        $response = $this->get('/blog/takoy-stati-nikogda-ne-bylo');

        $response->assertStatus(301);
        $this->assertStringEndsWith('/articles', $response->headers->get('Location'));
    }

    public function test_post_requests_are_not_redirected(): void
    {
        $request = \Illuminate\Http\Request::create('http://www.narepite.com/api/public-generate/lyrics/', 'POST');
        $response = (new \App\Http\Middleware\SeoRedirect)->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
