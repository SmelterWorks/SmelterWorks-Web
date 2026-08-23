@extends('layouts.guest', [
    'title' => 'Verify email',
    'heroTitle' => 'Check your inbox.',
    'heroCopy' => 'We sent a verification link to your email address. Click it to unlock the panel.',
])

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-50">Verify your email</h1>
        <p class="mt-2 text-sm text-zinc-400">
            We sent a link to <span class="text-zinc-200">{{ auth()->user()->email }}</span>.
            Open it to continue.
        </p>
    </div>

    <form method="post" action="{{ route('verification.send') }}" class="space-y-4">
        @csrf
        <button type="submit" class="auth-button">Resend verification email</button>
    </form>

    <form method="post" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-sm text-zinc-500 hover:text-zinc-300">Sign out</button>
    </form>
@endsection
