@extends('layouts.guest', [
    'title' => 'Create account',
    'heroTitle' => 'Set up your panel account.',
    'heroCopy' => 'Manage servers, daemons, backups, and files. Add a team name later if you want shared access.',
])

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-50">Create account</h1>
        <p class="mt-2 text-sm text-zinc-400">
            Already have access?
            <a href="{{ route('login') }}" class="font-medium text-ember-light hover:text-ember">Sign in</a>
        </p>
    </div>

    <form
        method="post"
        action="{{ route('register') }}"
        class="space-y-5"
        data-register-form
        data-password-policy='@json($passwordPolicy)'
    >
        @csrf

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-300">Your name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="auth-input @error('name') border-red-500/70 @enderror"
            >
            @error('name')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="team_name" class="mb-1.5 block text-sm font-medium text-zinc-300">
                Team <span class="font-normal text-zinc-500">(optional)</span>
            </label>
            <input
                id="team_name"
                type="text"
                name="team_name"
                value="{{ old('team_name') }}"
                autocomplete="organization"
                placeholder="Leave blank for a personal workspace"
                class="auth-input @error('team_name') border-red-500/70 @enderror"
            >
            @error('team_name')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-300">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
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
                autocomplete="new-password"
                class="auth-input @error('password') border-red-500/70 @enderror"
            >
            <div class="mt-3 space-y-2" data-password-meter>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-500">{{ $passwordSummary }}</span>
                    <span class="font-medium text-zinc-300" data-password-label>Enter a password</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-zinc-800">
                    <div class="h-full w-0 rounded-full bg-zinc-600 transition-all duration-200" data-password-bar></div>
                </div>
                <ul class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs" data-password-checks></ul>
            </div>
            @error('password')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-zinc-300">Confirm password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="auth-input"
            >
        </div>

        <x-altcha-widget :challenge-url="$altchaChallengeUrl" class="pt-1" />

        <button type="submit" class="auth-button">Create account</button>
    </form>
@endsection
