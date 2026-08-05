@props([
    'items' => [],
    'variant' => 'default',
])

<ul {{ $attributes->class(['feature-grid', 'feature-grid--' . $variant]) }}>
    @foreach ($items as $item)
        @php
            $icon = is_array($item) ? $item['icon'] ?? 'circle' : 'circle';
            $title = is_array($item) ? $item['title'] ?? null : null;
            $text = is_array($item) ? (string) ($item['text'] ?? '') : (string) $item;
            $pack = is_array($item) ? $item['pack'] ?? 'lucide' : 'lucide';
            $link = is_array($item) ? $item['link'] ?? null : null;
            $textHtml = e($text);

            if (is_array($link) && filled($link['label'] ?? null)) {
                $label = (string) $link['label'];
                $href = filled($link['route'] ?? null)
                    ? route((string) $link['route'])
                    : (string) ($link['href'] ?? '');

                if ($href !== '' && str_contains($textHtml, e($label))) {
                    $external = (bool) ($link['external'] ?? str_starts_with($href, 'http'));
                    $attrs = $external ? ' rel="noopener noreferrer" target="_blank"' : '';
                    $anchor =
                        '<a href="' . e($href) . '" class="feature-grid__link"' . $attrs . '>' . e($label) . '</a>';
                    $textHtml =
                        preg_replace('/' . preg_quote(e($label), '/') . '/', $anchor, $textHtml, 1) ?? $textHtml;
                }
            }
        @endphp
        <li class="feature-grid__item">
            <span class="feature-grid__icon" aria-hidden="true">
                <x-icon :name="$icon" :pack="$pack" :size="22" />
            </span>
            <span class="feature-grid__body">
                @if (filled($title))
                    <span class="feature-grid__title">{{ $title }}</span>
                @endif
                <span class="feature-grid__text">{!! $textHtml !!}</span>
            </span>
        </li>
    @endforeach
</ul>
