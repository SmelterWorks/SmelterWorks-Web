@props(['hosting'])

<div class="prose-block hosting-details">
    <h2>Server specs</h2>
    <ul class="spec-list">
        <li><strong>CPU:</strong> {{ $hosting['hardware']['cpu'] }} ({{ $hosting['hardware']['cpu_detail'] }})</li>
        <li><strong>Memory:</strong> {{ $hosting['hardware']['memory'] }}</li>
        <li><strong>Storage:</strong> {{ $hosting['hardware']['storage'] }}</li>
    </ul>
</div>

<div class="feature-section">
    <h2 class="feature-section__title">Included on every plan</h2>
    <x-feature-grid :items="$hosting['features']" />
</div>

<div class="prose-block hosting-details">
    <h2>Regions</h2>
    <ul class="region-list">
        @foreach ($hosting['regions'] as $region)
            <li>
                <span class="region-list__row">
                    @if (filled($region['flag'] ?? null))
                        <x-region-flag :code="$region['flag']" />
                    @endif
                    <span class="region-list__name">{{ $region['label'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>

    <h2>Refunds</h2>
    <p>{{ $hosting['refund']['intro'] }}</p>
    <ul>
        @foreach ($hosting['refund']['points'] as $point)
            <li>{{ $point }}</li>
        @endforeach
    </ul>

    <h2>Billing notes</h2>
    <ul>
        @foreach ($hosting['notes'] as $note)
            <li>{{ $note }}</li>
        @endforeach
    </ul>
</div>
