@props(['code', 'title', 'description', 'lede' => null])

<x-layouts.site :title="$title" :description="$description" robots="noindex, nofollow">
    <section class="page-hero">
        <div class="page-hero__inner">
            <p class="error-page__code" aria-hidden="true">{{ $code }}</p>
            <h1 class="page-hero__title">{{ $title }}</h1>
            @if (filled($lede))
                <p class="page-hero__lede">{{ $lede }}</p>
            @endif
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner">
            <div class="error-page__content prose-block">
                {{ $slot }}
            </div>

            @isset($actions)
                <div class="action-row">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </section>
</x-layouts.site>
