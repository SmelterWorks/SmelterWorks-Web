<x-layouts.site title="Order reserved" description="Your SmelterWorks hosting order was reserved.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Order reserved</h1>
            <p class="page-hero__lede">
                Stock is held in {{ $regionLabel }} for {{ $plan['name'] }}.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            @if (session('status'))
                <p class="flash-message">{{ session('status') }}</p>
            @endif

            <ul class="spec-list">
                <li><strong>Order ID:</strong> {{ $purchase->uuid }}</li>
                <li><strong>Plan:</strong> {{ $plan['name'] }} ({{ $plan['ram_gb'] }} GB)</li>
                <li><strong>Region:</strong> {{ $regionLabel }}</li>
                <li><strong>Billing:</strong> {{ $purchase->billing_cycle }} · ${{ $purchase->amount_usd }}</li>
                <li><strong>Email:</strong> {{ $purchase->customer_email }}</li>
                <li><strong>Status:</strong> {{ $purchase->status }}</li>
            </ul>

            <p>
                Card payment is not connected yet. Your slot stays reserved while we finish checkout.
                @if (filled(config('smelterworks.links.fluxer')))
                    Message Fluxer with this order ID if you need help.
                @else
                    Contact support with this order ID if you need help.
                @endif
            </p>

            <div class="action-row">
                <x-button href="{{ route('hosting') }}">Back to hosting</x-button>
                @if (filled(config('smelterworks.links.fluxer')))
                    <x-button href="{{ config('smelterworks.links.fluxer') }}" variant="ghost"
                        rel="noopener noreferrer" target="_blank">
                        Fluxer
                    </x-button>
                @endif
            </div>
        </div>
    </section>
</x-layouts.site>
