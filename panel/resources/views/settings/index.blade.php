@extends('layouts.app', ['title' => 'Settings', 'section' => 'Account'])

@section('content')
    <section class="panel-card max-w-2xl">
        <h2 class="mb-4 text-lg font-semibold text-zinc-50">Team settings</h2>
        @if ($user->isOwner())
            <form method="post" action="{{ route('settings.organization') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="panel-label">Team name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $organization?->name) }}" class="panel-input" required>
                </div>
                <div>
                    <label for="billing_email" class="panel-label">Billing email</label>
                    <input id="billing_email" type="email" name="billing_email" value="{{ old('billing_email', $organization?->billing_email) }}" class="panel-input">
                </div>
                <button type="submit" class="panel-btn panel-btn-primary">Save settings</button>
            </form>
        @else
            <p class="text-sm text-zinc-500">Only the team owner can change workspace settings.</p>
        @endif
    </section>
@endsection
