<x-error-page code="419" title="Session expired" description="Your form session expired before it was submitted."
    lede="Reload the page and try again.">
    <p>Forms on this site time out after a while. Reload the page, then resubmit.</p>

    <x-slot:actions>
        <x-button href="{{ route('hosting') }}">Hosting</x-button>
        <x-button href="{{ route('home') }}" variant="ghost">Home</x-button>
    </x-slot:actions>
</x-error-page>
