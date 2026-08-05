<x-layouts.site title="Branding" description="SmelterWorks logo marks for press and community use.">
    <section class="page-hero page-hero--compact">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Branding</h1>
            <p class="page-hero__lede">{{ $intro }}</p>
        </div>
    </section>

    <section class="section branding-page">
        <div class="section__inner">
            @foreach ($groups as $group)
                <div class="branding-group">
                    <div class="branding-group__intro">
                        <h2 class="branding-group__title">{{ $group['title'] }}</h2>
                        <p class="branding-group__lede">{{ $group['description'] }}</p>
                    </div>

                    <div class="branding-grid">
                        @foreach ($group['marks'] as $mark)
                            <x-branding-mark :mark="$mark" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.site>
