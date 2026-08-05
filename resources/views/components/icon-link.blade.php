@props(['href', 'label'])

<a href="{{ $href }}" class="icon-link" aria-label="{{ $label }}" title="{{ $label }}"
    rel="noopener noreferrer" target="_blank" {{ $attributes }}>
    {{ $slot }}
</a>
