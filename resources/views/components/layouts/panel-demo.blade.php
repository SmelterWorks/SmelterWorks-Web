@props([
    'title' => 'SmelterWorks Panel',
    'description' => 'Interactive preview of the SmelterWorks control panel.',
])

@php
    $pageTitle = $title . ' · Demo';
    $locale = str_replace('_', '-', (string) app()->getLocale());
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $pageTitle }}</title>

    <link rel="icon" href="{{ asset('images/brand/SmelterWorks-64.png') }}" type="image/png" sizes="64x64">

    @fonts
    @vite(['resources/css/panel-demo.css', 'resources/js/panel-demo.js'])
</head>

<body class="panel-app">
    {{ $slot }}
</body>

</html>
