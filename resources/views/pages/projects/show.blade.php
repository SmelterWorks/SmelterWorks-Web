<x-layouts.site :title="$project->name" :description="$project->summary">
    <section class="page-hero">
        <div class="page-hero__inner">
            <div class="page-hero__meta">
                <span class="pill">{{ $project->kindLabel() }}</span>
                <span class="pill pill--muted">{{ $project->statusLabel() }}</span>
            </div>
            <h1 class="page-hero__title">{{ $project->name }}</h1>
            <p class="page-hero__lede">{{ $project->summary }}</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <p>{{ $project->description }}</p>

            @if ($project->tags !== [])
                <ul class="tag-list" aria-label="Tags">
                    @foreach ($project->tags as $tag)
                        <li class="pill pill--muted">{{ $tag }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="action-row">
                @if ($project->repoUrl)
                    <x-button :href="$project->repoUrl" variant="solid" rel="noopener noreferrer" target="_blank">
                        Repository
                    </x-button>
                @endif
                @if ($project->modDbUrl)
                    <x-button :href="$project->modDbUrl" variant="ghost" rel="noopener noreferrer" target="_blank">
                        ModDB
                    </x-button>
                @endif
                <x-button :href="route('projects.index')" variant="ghost">
                    Back to projects
                </x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
