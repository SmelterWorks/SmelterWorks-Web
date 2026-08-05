@props(['regions' => []])

@php
    $markers = collect($regions)
        ->filter(
            fn(mixed $region): bool => is_array($region) &&
                is_numeric($region['lat'] ?? null) &&
                is_numeric($region['lng'] ?? null) &&
                filled($region['label'] ?? null),
        )
        ->map(
            fn(array $region): array => [
                'label' => (string) $region['label'],
                'lat' => (float) $region['lat'],
                'lng' => (float) $region['lng'],
            ],
        )
        ->values()
        ->all();
@endphp

<figure class="region-map" data-region-map>
    <div class="region-map__frame" data-region-map-canvas role="img"
        aria-label="Map of hosting regions in the United States and Germany"></div>
    <script type="application/json" data-region-map-markers>{!! json_encode($markers, JSON_THROW_ON_ERROR) !!}</script>
    <figcaption class="region-map__caption">
        Regions:
        @foreach ($markers as $index => $marker)
            {{ $index > 0 ? ' · ' : '' }}{{ $marker['label'] }}
        @endforeach
    </figcaption>
</figure>

@vite(['resources/js/region-map.js'])
