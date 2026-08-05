<x-error-page code="405" title="Method not allowed" description="This URL does not accept that request method."
    lede="The browser used the wrong HTTP method.">
    <p>Try opening the page normally, or go back to the home page.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Home</x-button>
    </x-slot:actions>
</x-error-page>
