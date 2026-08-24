@extends('layouts.app', ['title' => 'Get a server', 'section' => 'Hosting'])

@section('content')
    <form method="post" action="{{ route('servers.purchase.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        <div class="space-y-4 lg:col-span-2">
            @foreach ($plans as $plan)
                <label class="panel-card block cursor-pointer transition hover:border-ember/40">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="plan_slug" value="{{ $plan['slug'] }}" class="mt-1" @checked($loop->first) required>
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-semibold capitalize text-zinc-50">{{ $plan['slug'] }}</h3>
                                <p class="text-sm text-ember-light">${{ $plan['price_monthly'] }}/mo</p>
                            </div>
                            <p class="mt-1 text-sm text-zinc-400">{{ $plan['ram_gb'] }} GB RAM · {{ $plan['storage_gb'] }} GB storage</p>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="panel-card h-fit space-y-4">
            <h2 class="text-lg font-semibold text-zinc-50">Configuration</h2>
            <div>
                <label for="name" class="panel-label">Server name</label>
                <input id="name" type="text" name="name" class="panel-input" required>
            </div>
            <div>
                <label for="region_code" class="panel-label">Region</label>
                <select id="region_code" name="region_code" class="panel-input">
                    @foreach ($regions as $region)
                        <option value="{{ $region['code'] }}">{{ $region['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="billing_cycle" class="panel-label">Billing cycle</label>
                <select id="billing_cycle" name="billing_cycle" class="panel-input">
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div>
                <label for="type" class="panel-label">Hosting type</label>
                <select id="type" name="type" class="panel-input">
                    <option value="byos">Bring your own server (BYOS)</option>
                    @if ($managedMode)
                        <option value="managed">Managed hosting</option>
                    @endif
                </select>
            </div>
            @if (! $stripeEnabled)
                <p class="text-xs text-zinc-500">Stripe billing is disabled. Managed checkout requires billing to be enabled.</p>
            @endif
            @error('billing')<p class="panel-error">{{ $message }}</p>@enderror
            @error('type')<p class="panel-error">{{ $message }}</p>@enderror
            <button type="submit" class="panel-btn panel-btn-primary w-full">Continue</button>
        </div>
    </form>
@endsection
