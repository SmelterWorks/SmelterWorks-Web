<x-error-page code="500" title="Server error" description="SmelterWorks hit an unexpected server error."
    lede="Something broke on our side.">
    <p>Try again in a few minutes. If the error keeps showing up, tell us through the contact page.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Home</x-button>
        <x-button href="{{ route('contact') }}" variant="ghost">Contact</x-button>
    </x-slot:actions>
</x-error-page>
