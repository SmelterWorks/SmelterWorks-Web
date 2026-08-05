<x-error-page code="429" title="Too many requests" description="This site limited your request rate."
    lede="Wait a minute, then try again.">
    <p>You sent too many requests in a short window. Hosting checkout is limited to ten attempts per minute.</p>

    <x-slot:actions>
        <x-button href="{{ route('hosting') }}">Hosting</x-button>
        <x-button href="{{ route('home') }}" variant="ghost">Home</x-button>
    </x-slot:actions>
</x-error-page>
