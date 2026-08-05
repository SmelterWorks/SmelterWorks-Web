<x-layouts.site title="Mods" description="Vintage Story mods we publish.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Mods</h1>
            <p class="page-hero__lede">
                Vintage Story mods we publish.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner">
            <div class="project-list">
                @forelse ($mods as $mod)
                    <x-project-row :project="$mod" />
                @empty
                    <x-empty-state title="No mods yet">
                        <p>Nothing listed yet.</p>
                        <x-slot:actions>
                            <x-button href="{{ route('contribute') }}">Contribute</x-button>
                            <x-button href="{{ route('projects.index') }}" variant="ghost">Projects</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                @endforelse
            </div>

            @if ($mods->isNotEmpty())
                <div class="action-row">
                    <x-button href="{{ route('projects.index') }}">All projects</x-button>
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
