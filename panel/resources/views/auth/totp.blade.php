@extends('layouts.app', ['title' => 'Two-factor authentication'])

@section('content')
    <h1>Two-factor authentication</h1>
    <div class="card">
        @if ($enabled)
            <p>Authenticator app is enabled on this account.</p>
            <form method="post" action="{{ route('totp.disable') }}">
                @csrf
                <label>Authenticator code</label>
                <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" required>
                <button type="submit">Disable 2FA</button>
            </form>
        @else
            <p>Add an authenticator app for an extra login step.</p>
            <form method="post" action="{{ route('totp.begin') }}">
                @csrf
                <button type="submit">Generate setup secret</button>
            </form>
            @if (session('totp_secret'))
                <p>Secret: <code>{{ session('totp_secret') }}</code></p>
                <p>URI: <code>{{ session('totp_uri') }}</code></p>
                <form method="post" action="{{ route('totp.confirm') }}">
                    @csrf
                    <label>Confirm with a code</label>
                    <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" required>
                    <button type="submit">Enable 2FA</button>
                </form>
            @endif
        @endif
    </div>
@endsection
