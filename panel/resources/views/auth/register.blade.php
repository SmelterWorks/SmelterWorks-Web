@extends('layouts.app', ['title' => 'Register'])

@section('content')
    <div class="card">
        <h1>Register</h1>
        <form method="post" action="{{ route('register') }}">
            @csrf
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            <label>Organization</label>
            <input type="text" name="organization_name" value="{{ old('organization_name') }}" required>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<p class="error">{{ $message }}</p>@enderror
            <label>Password</label>
            <input type="password" name="password" required>
            @error('password')<p class="error">{{ $message }}</p>@enderror
            <label>Confirm password</label>
            <input type="password" name="password_confirmation" required>
            <button type="submit">Create account</button>
        </form>
    </div>
@endsection
