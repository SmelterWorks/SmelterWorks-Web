@props(['project'])

<article class="project-row">
    <div class="project-row__meta">
        <span class="pill">{{ $project->kindLabel() }}</span>
        <span class="pill pill--muted">{{ $project->statusLabel() }}</span>
    </div>

    <div class="project-row__body">
        <h3 class="project-row__title">
            <a href="{{ $project->url() }}">{{ $project->name }}</a>
        </h3>
        <p class="project-row__summary">{{ $project->summary }}</p>
    </div>

    <a class="project-row__action" href="{{ $project->url() }}">
        View
    </a>
</article>
