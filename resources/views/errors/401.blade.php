<x-error-page code="401" title="Sign in required" description="This page requires authentication."
    lede="You need permission before this page will load.">
    <p>This site does not have accounts yet. If you followed a link here, it may be wrong.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Home</x-button>
        <x-button href="{{ route('contact') }}" variant="ghost">Contact</x-button>
    </x-slot:actions>
</x-error-page>
