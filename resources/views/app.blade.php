<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seo = data_get($page ?? [], 'props.seo', []);
            $siteName = (string) data_get($page ?? [], 'props.appName', config('app.name', 'InkPavo'));
            $siteUrl = rtrim((string) data_get($page ?? [], 'props.appUrl', config('app.url', 'https://www.inkpavo.com')), '/');
            $seoTitle = (string) ($seo['full_title'] ?? $siteName);
            $seoDescription = (string) ($seo['description'] ?? 'Custom business cards and professional print products from InkPavo.');
            $seoRobots = (string) ($seo['robots'] ?? 'noindex,follow');
            $seoCanonical = (string) ($seo['canonical'] ?? ($siteUrl.request()->getPathInfo()));
            $seoType = (string) ($seo['type'] ?? 'website');
            $seoImage = $seo['image'] ?? null;
            $structuredData = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Organization',
                        'name' => $siteName,
                        'url' => $siteUrl,
                    ],
                    [
                        '@type' => 'WebSite',
                        'name' => $siteName,
                        'url' => $siteUrl,
                        'potentialAction' => [
                            '@type' => 'SearchAction',
                            'target' => $siteUrl.'/search?q={search_term_string}',
                            'query-input' => 'required name=search_term_string',
                        ],
                    ],
                ],
            ];
            if (is_array($seo['schema'] ?? null)) {
                $structuredData['@graph'][] = $seo['schema'];
            }
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="p:domain_verify" content="05cdb6e0fca5ae0f0efc2131d948432d">
        <meta name="robots" content="{{ $seoRobots }}" data-inertia="robots">
        <meta name="title" content="{{ $seoTitle }}" data-inertia="title">
        <meta name="description" content="{{ $seoDescription }}" data-inertia="description">
        <meta property="og:site_name" content="{{ $siteName }}" data-inertia="og:site_name">
        <meta property="og:locale" content="en_US" data-inertia="og:locale">
        <meta property="og:type" content="{{ $seoType }}" data-inertia="og:type">
        <meta property="og:url" content="{{ $seoCanonical }}" data-inertia="og:url">
        <meta property="og:title" content="{{ $seoTitle }}" data-inertia="og:title">
        <meta property="og:description" content="{{ $seoDescription }}" data-inertia="og:description">
        @if ($seo['published_at'] ?? null)
            <meta property="article:published_time" content="{{ $seo['published_at'] }}" data-inertia="article:published_time">
        @endif
        @if ($seo['modified_at'] ?? null)
            <meta property="article:modified_time" content="{{ $seo['modified_at'] }}" data-inertia="article:modified_time">
        @endif
        @if ($seo['author'] ?? null)
            <meta property="article:author" content="{{ $seo['author'] }}" data-inertia="article:author">
        @endif
        @if ($seoImage)
            <meta property="og:image" content="{{ $seoImage }}" data-inertia="og:image">
        @endif
        <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}" data-inertia="twitter:card">
        <meta name="twitter:url" content="{{ $seoCanonical }}" data-inertia="twitter:url">
        <meta name="twitter:title" content="{{ $seoTitle }}" data-inertia="twitter:title">
        <meta name="twitter:description" content="{{ $seoDescription }}" data-inertia="twitter:description">
        @if ($seoImage)
            <meta name="twitter:image" content="{{ $seoImage }}" data-inertia="twitter:image">
        @endif
        <link rel="canonical" href="{{ $seoCanonical }}" data-inertia="canonical">
        <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>

        {{-- InkPavo uses a light storefront theme regardless of browser settings. --}}
        <script>
            (function() {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.colorScheme = 'light';
            })();
        </script>

        {{-- Keep the first paint light before the application bundle loads. --}}
        <style>
            html {
                background-color: #FAF7F2;
                color-scheme: light;
            }
        </style>

        <link rel="icon" href="/favicon.png" type="image/png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        <x-inertia::head>
            <title>{{ $seoTitle }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
