@extends('layouts.app', ['title' => 'Login'])

@section('content')
    <div class="card">
        <h1>Login</h1>
        <form method="post" action="{{ route('login') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<p class="error">{{ $message }}</p>@enderror
            <label>Password</label>
            <input type="password" name="password" required>
            <label><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button type="submit">Sign in</button>
        </form>
        <p><a href="{{ route('register') }}">Create account</a></p>
    </div>
@endsection
