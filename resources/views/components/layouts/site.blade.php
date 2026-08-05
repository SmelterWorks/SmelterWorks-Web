@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'imageAlt' => null,
    'type' => 'website',
    'canonical' => null,
    'robots' => 'index, follow',
    'jsonLd' => [],
    'rssUrl' => null,
    'rssTitle' => null,
])

@php
    use App\Support\Seo\JsonLdBuilder;

    $pageTitle = $title
        ? $title . ' · ' . config('app.name')
        : config('app.name') . ' · ' . config('smelterworks.tagline');
    $pageDescription = $description ?? config('smelterworks.mission');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImage = filled($image)
        ? (str_starts_with((string) $image, 'http://') || str_starts_with((string) $image, 'https://')
            ? (string) $image
            : asset((string) $image))
        : asset(config('smelterworks.seo.image', 'images/brand/SmelterWorks-512.png'));
    $ogImageAlt = $imageAlt ?? ($title ?? config('app.name'));
    $siteName = (string) config('app.name');
    $locale = str_replace('_', '-', (string) app()->getLocale());
    $jsonLdPayload = app(JsonLdBuilder::class)->encode(is_array($jsonLd) ? $jsonLd : []);
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">

    <title>{{ $pageTitle }}</title>

    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $locale }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
    @if (filled(config('smelterworks.seo.twitter')))
        <meta name="twitter:site" content="{{ config('smelterworks.seo.twitter') }}">
    @endif

    <link rel="icon" href="{{ asset('images/brand/SmelterWorks-64.png') }}" type="image/png" sizes="64x64">
    <link rel="icon" href="{{ asset('images/brand/SmelterWorks-128.png') }}" type="image/png" sizes="128x128">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/SmelterWorks-256.png') }}">

    @if (filled($rssUrl))
        <link rel="alternate" type="application/rss+xml" title="{{ $rssTitle ?? $title . ' feed' }}"
            href="{{ $rssUrl }}">
    @endif

    <script type="application/ld+json">{!! $jsonLdPayload !!}</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-dvh bg-ash text-iron antialiased">
    <a href="#main" class="skip-link">Skip to content</a>

    <div class="site-shell">
        <div class="site-top">
            @include('partials.header')
            <x-site-banner />
        </div>

        <main id="main">
            {{ $slot }}
        </main>

        @include('partials.footer')
    </div>
</body>

</html>
