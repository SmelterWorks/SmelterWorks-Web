@php
    $code = (int) ($code ?? 400);
    $title = match ($code) {
        402 => 'Payment required',
        408 => 'Request timed out',
        410 => 'Gone',
        502 => 'Bad gateway',
        504 => 'Gateway timed out',
        default => 'Request error',
    };
    $lede = match ($code) {
        402 => 'Payment is required before this page loads.',
        408 => 'The server stopped waiting for the request.',
        410 => 'This page was removed and is not coming back.',
        502, 504 => 'An upstream service failed to respond.',
        default => 'The request could not be completed.',
    };
@endphp

<x-error-page :code="$code" :title="$title" :description="'SmelterWorks returned HTTP ' . $code . '.'" :lede="$lede">
    <p>If you followed a link here, it may be outdated. Otherwise, wait a minute and try again.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Home</x-button>
        <x-button href="{{ route('contact') }}" variant="ghost">Contact</x-button>
    </x-slot:actions>
</x-error-page>
