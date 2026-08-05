<x-error-page code="404" title="Page not found" description="That URL is not on the SmelterWorks site."
    lede="The link may be wrong or the page moved.">
    <p>Check the URL, or use the links below.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Home</x-button>
        <x-button href="{{ route('projects.index') }}" variant="ghost">Projects</x-button>
    </x-slot:actions>
</x-error-page>
