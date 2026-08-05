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
    @if (filled($hosting['change_note'] ?? null))
        <p class="section__note">{{ $hosting['change_note'] }}</p>
    @endif
</div>

<div class="prose-block hosting-details">
    <h2>Regions</h2>
    <x-hosting-region-map :regions="$hosting['regions']" />

    <h2>Refunds</h2>
    <p>{{ $hosting['refund']['intro'] }}</p>
    <ul class="note-list">
        @foreach ($hosting['refund']['points'] as $point)
            <li>{{ $point }}</li>
        @endforeach
    </ul>

    <h2>Billing notes</h2>
    <ul class="note-list">
        @foreach ($hosting['notes'] as $note)
            <li>{{ $note }}</li>
        @endforeach
    </ul>
</div>
