<x-layouts.site title="Donate" description="Support SmelterWorks server hardware and open-source Vintage Story projects.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Donate</h1>
            <p class="page-hero__lede">
                {{ $donate['intro'] }}
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <p>
                Server hardware and hosting costs are paid directly by Ivan. SmelterWorks does not run
                for profit. Donations help keep the servers online and the projects moving.
            </p>

            @if (filled($donate['kofi_url']))
                <h2>Ko-Fi</h2>
                <p>
                    <a href="{{ $donate['kofi_url'] }}" class="donate-link" rel="noopener noreferrer" target="_blank">
                        <x-icon name="kofi" pack="simple" :size="20" />
                        <span>Ko-Fi</span>
                    </a>
                </p>
            @endif

            <p>
                When hosting checkout is ready, you can add an optional tip there as well.
            </p>

            <div class="action-row">
                <x-button href="{{ route('about') }}" variant="ghost">About</x-button>
                <x-button href="{{ route('contact') }}" variant="ghost">Contact</x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
