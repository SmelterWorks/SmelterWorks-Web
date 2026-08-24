@extends('layouts.app', ['title' => 'Security', 'section' => 'Account'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel-card">
            <h2 class="mb-4 text-lg font-semibold text-zinc-50">Two-factor authentication</h2>
            @if ($enabled)
                <p class="mb-4 text-sm text-zinc-400">Authenticator app is enabled.</p>
                <form method="post" action="{{ route('settings.security.disable') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="code" class="panel-label">Authenticator code</label>
                        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" class="panel-input" required>
                        @error('code')<p class="panel-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="panel-btn panel-btn-danger">Disable 2FA</button>
                </form>
            @else
                <p class="mb-4 text-sm text-zinc-400">Scan the QR code with your authenticator app, then enter a code to confirm.</p>
                <form method="post" action="{{ route('settings.security.begin') }}" class="mb-6">
                    @csrf
                    <button type="submit" class="panel-btn panel-btn-ghost">Generate setup QR</button>
                </form>
                @if (session('totp_uri'))
                    <div class="mb-4 flex flex-col items-start gap-4 sm:flex-row">
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(session('totp_uri')) }}"
                            alt="2FA QR code"
                            width="180"
                            height="180"
                            class="rounded-lg border border-border bg-white p-2"
                        >
                        <div class="text-sm text-zinc-400">
                            <p class="mb-2">Manual secret:</p>
                            <code class="break-all text-ember-light">{{ session('totp_secret') }}</code>
                        </div>
                    </div>
                    <form method="post" action="{{ route('settings.security.confirm') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="panel-label">Verification code</label>
                            <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" class="panel-input" required>
                            @error('code')<p class="panel-error">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="panel-btn panel-btn-primary">Enable 2FA</button>
                    </form>
                @endif
            @endif
        </section>

        @if ($recoveryCodes)
            <section class="panel-card">
                <h2 class="mb-4 text-lg font-semibold text-zinc-50">Recovery codes</h2>
                <p class="mb-4 text-sm text-zinc-400">Store these somewhere safe. Each code works once.</p>
                <ul class="grid grid-cols-2 gap-2 font-mono text-sm text-ember-light">
                    @foreach ($recoveryCodes as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
