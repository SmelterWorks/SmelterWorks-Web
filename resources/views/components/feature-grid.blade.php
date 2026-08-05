@props([
    'items' => [],
    'variant' => 'default',
])

<ul {{ $attributes->class(['feature-grid', 'feature-grid--' . $variant]) }}>
    @foreach ($items as $item)
        @php
            $icon = is_array($item) ? $item['icon'] ?? 'circle' : 'circle';
            $text = is_array($item) ? $item['text'] ?? '' : $item;
            $pack = is_array($item) ? $item['pack'] ?? 'lucide' : 'lucide';
        @endphp
        <li class="feature-grid__item">
            <span class="feature-grid__icon" aria-hidden="true">
                <x-icon :name="$icon" :pack="$pack" :size="22" />
            </span>
            <span class="feature-grid__text">{{ $text }}</span>
        </li>
    @endforeach
</ul>
