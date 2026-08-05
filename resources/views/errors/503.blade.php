<x-error-page code="503" title="Back soon" description="SmelterWorks is temporarily unavailable."
    lede="Maintenance or overload.">
    <p>We are deploying changes or the site is under heavy load. Reload in a minute.</p>

    <x-slot:actions>
        <x-button href="{{ route('home') }}">Try again</x-button>
    </x-slot:actions>
</x-error-page>
