@props(['platform', 'label', 'detail' => null])

<div {{ $attributes->class('platform-chip') }}>
    <span class="platform-chip__icon" aria-hidden="true">
        <x-platform-icon :platform="$platform" :size="28" />
    </span>
    <span class="platform-chip__text">
        <span class="platform-chip__label">{{ $label }}</span>
        @if ($detail)
            <span class="platform-chip__detail">{{ $detail }}</span>
        @endif
    </span>
</div>
