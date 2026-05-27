@php
    /** @var array<string> $include */
    $include = $include ?? [];

    $siteUrl = rtrim(config('site.url'), '/');
    $orgId = $siteUrl . '/#organization';
    $webId = $siteUrl . '/#website';
    $appId = $siteUrl . '/#webapp';

    $mirrorMap = config('site.best_songs.mirror_domains', []);
    $absUrl = function ($url) use ($siteUrl, $mirrorMap) {
        if (!$url) return null;
        foreach ($mirrorMap as $from => $to) {
            $url = str_replace($from, $to, $url);
        }
        if (str_starts_with($url, '/')) {
            return $siteUrl . $url;
        }
        return $url;
    };

    $graph = [];

    if (in_array('organization', $include, true)) {
        $org = config('site.organization');
        $graph[] = [
            '@type' => 'Organization',
            '@id' => $orgId,
            'name' => $org['name'],
            'url' => $siteUrl,
            'email' => $org['email'],
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $siteUrl . $org['logo']['path'],
                'width' => $org['logo']['width'],
                'height' => $org['logo']['height'],
            ],
            'sameAs' => array_values($org['same_as']),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => $org['contact_point']['contact_type'],
                'availableLanguage' => $org['contact_point']['available_language'],
                'email' => $org['email'],
            ],
        ];
    }

    if (in_array('website', $include, true)) {
        $web = config('site.website');
        $graph[] = [
            '@type' => 'WebSite',
            '@id' => $webId,
            'name' => $web['name'],
            'url' => $siteUrl,
            'description' => $web['description'],
            'publisher' => ['@id' => $orgId],
        ];
    }

    if (in_array('webapp', $include, true)) {
        $app = config('site.webapp');
        $graph[] = [
            '@type' => 'WebApplication',
            '@id' => $appId,
            'name' => $app['name'],
            'description' => $app['description'],
            'applicationCategory' => $app['application_category'],
            'operatingSystem' => $app['operating_system'],
            'inLanguage' => $app['in_language'],
            'url' => $siteUrl . $app['url_path'],
            'featureList' => $app['feature_list'],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => $app['offer']['price_currency'],
                'price' => $app['offer']['price'],
                'priceValidUntil' => $app['offer']['price_valid_until'],
                'availability' => $app['offer']['availability'],
                'url' => $siteUrl . $app['offer']['url_path'],
            ],
            'provider' => ['@id' => $orgId],
        ];
    }

    if (in_array('best-songs', $include, true) && !empty($topTracks ?? null)) {
        $imageW = (int) config('site.best_songs.image_width', 200);
        $imageH = (int) config('site.best_songs.image_height', 200);

        $elements = [];
        foreach ($topTracks as $i => $track) {
            $item = [
                '@type' => 'MusicComposition',
                'name' => $track['title'] ?? 'Без названия',
                'inLanguage' => 'ru',
                'creator' => [
                    '@type' => 'Person',
                    'name' => $track['author'] ?? 'Автор',
                ],
            ];
            if (!empty($track['genre'])) {
                $item['genre'] = $track['genre'];
            }
            if (!empty($track['audio_url'])) {
                $item['audio'] = $absUrl($track['audio_url']);
            }
            if (!empty($track['cover_url'])) {
                $item['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $absUrl($track['cover_url']),
                    'width' => $imageW,
                    'height' => $imageH,
                ];
            }
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => $item,
            ];
        }

        if (!empty($elements)) {
            $graph[] = [
                '@type' => 'ItemList',
                'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                'numberOfItems' => count($elements),
                'itemListElement' => $elements,
            ];
        }
    }

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
