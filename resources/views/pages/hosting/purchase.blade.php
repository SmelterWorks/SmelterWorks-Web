<x-layouts.site :title="'Purchase ' . $plan['name']" description="Buy SmelterWorks Vintage Story hosting."
    robots="noindex, nofollow">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Purchase {{ $plan['name'] }}</h1>
            <p class="page-hero__lede">
                {{ $plan['blurb'] }} {{ $plan['ram_gb'] }} GB RAM, {{ $plan['storage_gb'] }} GB NVMe.
                {{ $remaining }} left across both regions.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner purchase-layout">
            <div class="prose-block">
                <h2>Stock by region</h2>
                <ul>
                    @forelse ($stocks as $stock)
                        <li>
                            {{ collect(config('smelterworks.hosting.regions'))->firstWhere('code', $stock->region_code)['label'] ?? $stock->region_code }}:
                            {{ $stock->remaining() }} of {{ $stock->capacity }} left
                        </li>
                    @empty
                        <li>Stock is not configured yet.</li>
                    @endforelse
                </ul>
                <p>
                    ${{ $plan['price_monthly'] }}/month
                    @if ($priceMonthlyEur)
                        (about €{{ number_format($priceMonthlyEur, 2) }})
                    @endif
                    or ${{ $plan['price_yearly'] }}/year
                    @if ($priceYearlyEur)
                        (about €{{ number_format($priceYearlyEur, 2) }})
                    @endif
                    .
                </p>
            </div>

            @if ($remaining < 1)
                <p class="flash-message flash-message--warn">This plan is sold out in both regions.</p>
                <x-button :href="route('hosting')" variant="ghost">Back to hosting</x-button>
            @else
                <form method="post" action="{{ route('hosting.purchase.store') }}" class="purchase-form">
                    @csrf
                    <input type="hidden" name="plan_slug" value="{{ $plan['slug'] }}">

                    <label class="field">
                        <span class="field__label">Region</span>
                        <select name="region_code" required>
                            @foreach ($regions as $region)
                                @php
                                    $row = $stocks->firstWhere('region_code', $region['code']);
                                    $left = $row?->remaining() ?? 0;
                                @endphp
                                <option value="{{ $region['code'] }}" @selected(old('region_code', $region['code']) === $region['code'])
                                    @disabled($left < 1)>
                                    {{ $region['label'] }} ({{ $left }} left)
                                </option>
                            @endforeach
                        </select>
                        @error('region_code')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </label>

                    <fieldset class="field">
                        <legend class="field__label">Billing</legend>
                        <label class="choice">
                            <input type="radio" name="billing_cycle" value="monthly" @checked(old('billing_cycle', 'monthly') === 'monthly')>
                            <span>Monthly · ${{ $plan['price_monthly'] }}</span>
                        </label>
                        <label class="choice">
                            <input type="radio" name="billing_cycle" value="yearly" @checked(old('billing_cycle') === 'yearly')>
                            <span>Yearly · ${{ $plan['price_yearly'] }} (save ${{ $plan['yearly_savings'] }})</span>
                        </label>
                        @error('billing_cycle')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </fieldset>

                    <label class="field">
                        <span class="field__label">Your name</span>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                            maxlength="120" autocomplete="name">
                        @error('customer_name')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="field">
                        <span class="field__label">Email</span>
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}" required
                            maxlength="255" autocomplete="email">
                        @error('customer_email')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="field">
                        <span class="field__label">Server name (optional)</span>
                        <input type="text" name="server_name" value="{{ old('server_name') }}" maxlength="64"
                            placeholder="My Vintage Story world">
                        @error('server_name')
                            <span class="field__error">{{ $message }}</span>
                        @enderror
                    </label>

                    <p class="form-note">
                        Submitting reserves one slot on the chosen host. Payment wiring comes next.
                        Until then the order stays pending and the stock stays held.
                    </p>

                    <div class="action-row">
                        <button type="submit" class="button button--solid">Reserve and continue</button>
                        <x-button :href="route('hosting')" variant="ghost">Cancel</x-button>
                    </div>
                </form>
            @endif
        </div>
    </section>
</x-layouts.site>
