@props([
    'title' => 'Nothing here yet',
])

<div {{ $attributes->class('empty-state') }}>
    <p class="empty-state__title">{{ $title }}</p>
    <div class="empty-state__body">
        {{ $slot }}
    </div>
    @isset($actions)
        <div class="empty-state__actions action-row">
            {{ $actions }}
        </div>
    @endisset
</div>
