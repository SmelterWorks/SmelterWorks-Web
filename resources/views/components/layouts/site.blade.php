@props([
    'title' => null,
    'description' => null,
    'rssUrl' => null,
    'rssTitle' => null,
])

@php
    $pageTitle = $title
        ? $title . ' · ' . config('app.name')
        : config('app.name') . ' · ' . config('smelterworks.tagline');
    $pageDescription = $description ?? config('smelterworks.mission');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription }}">

    <title>{{ $pageTitle }}</title>

    <link rel="icon" href="{{ asset('images/brand/SmelterWorks-64.png') }}" type="image/png" sizes="64x64">
    <link rel="icon" href="{{ asset('images/brand/SmelterWorks-128.png') }}" type="image/png" sizes="128x128">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/SmelterWorks-256.png') }}">

    @if (filled($rssUrl))
        <link rel="alternate" type="application/rss+xml" title="{{ $rssTitle ?? $title . ' feed' }}"
            href="{{ $rssUrl }}">
    @endif

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
