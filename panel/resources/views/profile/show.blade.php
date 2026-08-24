@extends('layouts.app', ['title' => 'Profile', 'section' => 'Account'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel-card">
            <h2 class="mb-4 text-lg font-semibold text-zinc-50">Profile</h2>
            <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="panel-label">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="panel-input" required>
                    @error('name')<p class="panel-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="panel-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="panel-input" required>
                    @error('email')<p class="panel-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="panel-btn panel-btn-primary">Save profile</button>
            </form>
        </section>

        <section class="panel-card">
            <h2 class="mb-4 text-lg font-semibold text-zinc-50">Change password</h2>
            <form method="post" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="panel-label">Current password</label>
                    <input id="current_password" type="password" name="current_password" class="panel-input" required>
                    @error('current_password')<p class="panel-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="panel-label">New password</label>
                    <input id="password" type="password" name="password" class="panel-input" required>
                    @error('password')<p class="panel-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="panel-label">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="panel-input" required>
                </div>
                <button type="submit" class="panel-btn panel-btn-primary">Update password</button>
            </form>
        </section>
    </div>
@endsection
