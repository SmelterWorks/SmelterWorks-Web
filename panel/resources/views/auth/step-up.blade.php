@extends('layouts.app', ['title' => 'Confirm'])

@section('content')
    <div class="card">
        <h1>Confirm your password</h1>
        <p>This action needs a fresh sign-in because your session looks different.</p>
        <form method="post" action="{{ $intended }}">
            @csrf
            <label>Password</label>
            <input type="password" name="password" required>
            @error('password')<p class="error">{{ $message }}</p>@enderror
            <button type="submit">Continue</button>
        </form>
    </div>
@endsection
