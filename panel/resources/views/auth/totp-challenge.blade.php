@extends('layouts.app', ['title' => 'Authenticator challenge'])

@section('content')
    <h1>Authenticator code</h1>
    <div class="card">
        <form method="post" action="{{ route('totp.verify') }}">
            @csrf
            <label>6-digit code</label>
            <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            <button type="submit">Continue</button>
        </form>
    </div>
@endsection
