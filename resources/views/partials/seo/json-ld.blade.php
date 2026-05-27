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
        if (str_starts_with($url, 'http://')) {
            $url = 'https://' . substr($url, 7);
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

    if (in_array('howto', $include, true) && !empty($article ?? null)) {
        $html = $article->content_html ?? '';
        $stepRegex = '/<(?:b|strong)>\s*(Шаг\s+\d+.*?)\s*<\/(?:b|strong)>/iu';

        if (preg_match_all($stepRegex, $html, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER)) {
            $matchCount = count($matches[0]);

            if ($matchCount >= 2) {
                $steps = [];
                for ($i = 0; $i < $matchCount; $i++) {
                    $stepFull = $matches[0][$i][0];
                    $stepHeading = trim(strip_tags($matches[1][$i][0]));
                    $stepStart = $matches[0][$i][1];
                    $stepEnd = $stepStart + strlen($stepFull);

                    if ($i + 1 < $matchCount) {
                        $nextStart = $matches[0][$i + 1][1];
                        $blockHtml = substr($html, $stepEnd, $nextStart - $stepEnd);
                    } else {
                        $blockHtml = substr($html, $stepEnd);
                    }

                    $stepImage = null;
                    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $blockHtml, $imgMatch)) {
                        $stepImage = $absUrl($imgMatch[1]);
                    }

                    $stepText = trim(strip_tags($blockHtml));

                    $stepEntity = [
                        '@type' => 'HowToStep',
                        'name' => $stepHeading,
                        'text' => $stepText,
                    ];
                    if ($stepImage) {
                        $stepEntity['image'] = $stepImage;
                    }
                    $steps[] = $stepEntity;
                }

                $howTo = [
                    '@type' => 'HowTo',
                    'name' => $article->title,
                    'description' => $article->final_seo_description,
                    'step' => $steps,
                    'tool' => [
                        '@type' => 'HowToTool',
                        'name' => 'Suno AI',
                    ],
                ];

                if (!empty($article->reading_time) && (int) $article->reading_time > 0) {
                    $howTo['totalTime'] = 'PT' . (int) $article->reading_time . 'M';
                }

                $graph[] = $howTo;
            }
        }
    }

    if (in_array('blog-posting', $include, true) && !empty($article ?? null)) {
        $articleUrl = $siteUrl . '/articles/' . $article->slug;

        $imageUrl = null;
        foreach (['og_image', 'banner_url', 'cover_url'] as $field) {
            if (!empty($article->{$field})) {
                $imageUrl = $absUrl($article->{$field});
                break;
            }
        }

        $entity = [
            '@type' => 'BlogPosting',
            'headline' => $article->title,
            'url' => $articleUrl,
            'mainEntityOfPage' => $articleUrl,
            'description' => $article->final_seo_description,
            'publisher' => ['@id' => $orgId],
        ];

        if ($imageUrl) {
            $entity['image'] = [
                '@type' => 'ImageObject',
                'url' => $imageUrl,
            ];
        }

        if ($article->published_at) {
            $entity['datePublished'] = $article->published_at->toIso8601String();
        }
        if ($article->updated_at) {
            $entity['dateModified'] = $article->updated_at->toIso8601String();
        }

        $articleBody = trim(strip_tags($article->content_html ?? ''));
        if (!empty($articleBody)) {
            $entity['articleBody'] = $articleBody;
        }

        $graph[] = $entity;
    }

    if (in_array('help', $include, true)) {
        $help = config('site.help');
        $orgConfig = config('site.organization');
        $helpPage = $page ?? null;

        $webPage = [
            '@type' => 'WebPage',
            'name' => $helpPage->title ?? 'Помощь',
            'url' => $siteUrl . $help['url_path'],
            'mainEntity' => [
                '@type' => 'ContactPoint',
                'contactType' => $help['contact_type'],
                'availableLanguage' => $help['available_language'],
                'email' => $orgConfig['email'],
            ],
        ];

        $desc = $helpPage->final_seo_description ?? null;
        if (!empty($desc)) {
            $webPage['description'] = $desc;
        }

        $graph[] = $webPage;
    }

    if (in_array('tariff', $include, true)) {
        $tariff = config('site.tariff');
        $packages = config('yookassa.packages', []);
        $tariffUrl = $siteUrl . $tariff['url_path'];

        $itemList = [];
        $pos = 1;
        foreach ($packages as $songCount => $pkg) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'item' => [
                    '@type' => 'Offer',
                    'name' => $pkg['name'],
                    'description' => $pkg['name'] . ' за ' . $pkg['price'] . '₽',
                    'priceSpecification' => [
                        '@type' => 'UnitPriceSpecification',
                        'priceCurrency' => $tariff['price_currency'],
                        'price' => $pkg['price'],
                        'referenceQuantity' => [
                            '@type' => 'QuantitativeValue',
                            'value' => (int) $songCount,
                            'unitText' => $tariff['unit_text'],
                        ],
                    ],
                    'availability' => 'https://schema.org/InStock',
                    'url' => $tariffUrl,
                ],
            ];
        }

        $graph[] = [
            '@type' => 'Service',
            'name' => $tariff['name'],
            'serviceType' => $tariff['service_type'],
            'description' => $tariff['description'],
            'areaServed' => $tariff['area_served'],
            'provider' => ['@id' => $orgId],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => $tariff['offer_catalog_name'],
                'itemListElement' => $itemList,
            ],
        ];
    }

    if (in_array('site-nav', $include, true)) {
        $hasPart = [
            ['@type' => 'WebPage', 'name' => 'Главная', 'url' => $siteUrl],
            ['@type' => 'WebPage', 'name' => 'Статьи', 'url' => $siteUrl . '/articles'],
        ];

        if (!empty($menuPages ?? null)) {
            foreach ($menuPages as $p) {
                if ($p->children && $p->children->isNotEmpty()) {
                    $url = $siteUrl . '/pages/' . $p->slug . '/' . $p->children->first()->slug;
                } else {
                    $url = $siteUrl . '/pages/' . $p->slug;
                }
                $hasPart[] = ['@type' => 'WebPage', 'name' => $p->title, 'url' => $url];
            }
        }

        if (!empty($menuStaticPages ?? null)) {
            foreach ($menuStaticPages as $sp) {
                $hasPart[] = ['@type' => 'WebPage', 'name' => $sp->title, 'url' => $siteUrl . '/' . $sp->slug];
            }
        }

        $hasPart[] = ['@type' => 'WebPage', 'name' => 'Создать трек', 'url' => $siteUrl . '/create-song'];
        $hasPart[] = ['@type' => 'WebPage', 'name' => 'Вход', 'url' => $siteUrl . '/login'];
        $hasPart[] = ['@type' => 'WebPage', 'name' => 'Регистрация', 'url' => $siteUrl . '/register'];

        $graph[] = [
            '@type' => 'SiteNavigationElement',
            'name' => 'Главное меню',
            'hasPart' => $hasPart,
        ];
    }

    if (in_array('breadcrumb', $include, true) && !empty($breadcrumbs ?? null)) {
        $crumbs = array_values($breadcrumbs);
        $count = count($crumbs);
        $elements = [];
        foreach ($crumbs as $i => $crumb) {
            $element = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
            ];
            if ($i < $count - 1 && !empty($crumb['url'])) {
                $element['item'] = $crumb['url'];
            }
            $elements[] = $element;
        }
        if (!empty($elements)) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
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
