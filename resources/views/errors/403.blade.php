<x-error-page code="403" title="Access denied" description="You do not have permission to view this page."
    lede="This request was blocked.">
    <p>You do not have permission to open this URL. If you think that is a mistake, use the contact page.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Home</x-button>
        <x-button href="{{ route('contact') }}" variant="ghost">Contact</x-button>
    </x-slot:actions>
</x-error-page>
