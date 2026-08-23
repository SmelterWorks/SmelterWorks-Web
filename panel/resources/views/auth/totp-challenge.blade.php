@extends('layouts.guest', [
    'title' => 'Authenticator code',
    'heroTitle' => 'Two-factor check.',
    'heroCopy' => 'Enter the 6-digit code from your authenticator app to finish signing in.',
])

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-50">Authenticator code</h1>
        <p class="mt-2 text-sm text-zinc-400">Open your authenticator app and enter the current code.</p>
    </div>

    <form method="post" action="{{ route('totp.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="mb-1.5 block text-sm font-medium text-zinc-300">6-digit code</label>
            <input
                id="code"
                type="text"
                name="code"
                inputmode="numeric"
                pattern="[0-9]{6}"
                required
                autofocus
                autocomplete="one-time-code"
                class="auth-input text-center text-lg tracking-[0.35em] @error('code') border-red-500/70 @enderror"
            >
            @error('code')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="auth-button">Continue</button>
    </form>
@endsection
