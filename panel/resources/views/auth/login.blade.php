@extends('layouts.guest', [
    'title' => 'Sign in',
    'heroTitle' => 'Welcome back.',
    'heroCopy' => 'Sign in to manage servers, daemons, backups, and files.',
])

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-50">Sign in</h1>
        <p class="mt-2 text-sm text-zinc-400">
            New here?
            <a href="{{ route('register') }}" class="font-medium text-ember-light hover:text-ember">Create an account</a>
        </p>
    </div>

    <form method="post" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-300">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="auth-input @error('email') border-red-500/70 @enderror"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-300">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="auth-input @error('password') border-red-500/70 @enderror"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-zinc-400">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-border bg-surface text-ember focus:ring-ember">
            Remember this device
        </label>

        <x-altcha-widget :challenge-url="$altchaChallengeUrl" class="pt-1" />

        <button type="submit" class="auth-button">Sign in</button>
    </form>
@endsection
