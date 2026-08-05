<x-layouts.site title="Projects" description="Mods, tools, and libraries from the SmelterWorks community.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Projects</h1>
            <p class="page-hero__lede">
                A living catalog of SmelterWorks software and Vintage Story mods.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner">
            <div class="project-list">
                @forelse ($projects as $project)
                    <x-project-row :project="$project" title-tag="h2" />
                @empty
                    <x-empty-state title="No projects yet">
                        <p>Published software and mods will appear here.</p>
                    </x-empty-state>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.site>
