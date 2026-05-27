@php
    $siteUrl = rtrim(config('site.url'), '/');
    $org = config('site.organization');
    $web = config('site.website');

    $orgId = $siteUrl . '/#organization';
    $webId = $siteUrl . '/#website';

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
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
            ],
            [
                '@type' => 'WebSite',
                '@id' => $webId,
                'name' => $web['name'],
                'url' => $siteUrl,
                'description' => $web['description'],
                'publisher' => ['@id' => $orgId],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
