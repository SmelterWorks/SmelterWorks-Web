<x-layouts.site title="About"
    description="SmelterWorks open-source tools and mods for Vintage Story, built by Ivan (Sudo-Ivan).">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">About</h1>
            <p class="page-hero__lede">
                {{ $about['founder_note'] }}
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <p>
                {{ config('smelterworks.mission') }}
            </p>
            <p>
                Server hardware is paid directly out of pocket. SmelterWorks is not run for profit.
                When hosting checkout opens, you can add an optional donation there. You can also
                <a href="{{ route('donate') }}">donate separately</a> to help with hardware and upkeep.
            </p>
            <p>
                SmelterWorks is independent. Vintage Story is created by Anego Studios.
            </p>

            <div class="action-row">
                <x-button href="{{ route('contribute') }}">Contribute</x-button>
                <x-button href="{{ route('donate') }}" variant="ghost">Donate</x-button>
                <x-button href="{{ route('contact') }}" variant="ghost">Contact</x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
