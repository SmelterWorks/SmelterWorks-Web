<x-layouts.site title="Order reserved" description="Your SmelterWorks hosting order was reserved."
    robots="noindex, nofollow">
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
                @if ($purchase->status === 'paid')
                    Payment received. @if ($purchase->provisioned_server_uuid)
                        Server UUID: <code>{{ $purchase->provisioned_server_uuid }}</code>
                    @else
                        Provisioning is in progress.
                    @endif
                @else
                    Complete payment in Stripe to activate this order.
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
