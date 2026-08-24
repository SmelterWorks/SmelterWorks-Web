<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('panel.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface text-zinc-100 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 border-r border-border bg-surface-raised lg:flex lg:flex-col">
            <div class="border-b border-border px-5 py-5">
                <a href="{{ route('dashboard') }}" class="inline-flex no-underline">
                    <x-brand-logo :size="36" />
                </a>
            </div>
            <nav class="flex flex-1 flex-col gap-1 p-3 text-sm">
                <x-panel.nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">Dashboard</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('servers.purchase') }}" :active="request()->routeIs('servers.purchase*')">Get a server</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('daemons.pairing') }}" :active="request()->routeIs('daemons.*')">BYOS pairing</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('subusers.index') }}" :active="request()->routeIs('subusers.*')">Team access</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('chat.index') }}" :active="request()->routeIs('chat.*')">Chat</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('support.index') }}" :active="request()->routeIs('support.*')">Support</x-panel.nav-link>

                <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">Account</p>
                <x-panel.nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.*')">Profile</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('settings.index') }}" :active="request()->routeIs('settings.*') && !request()->routeIs('settings.security')">Settings</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('settings.security') }}" :active="request()->routeIs('settings.security')">Security</x-panel.nav-link>
                <x-panel.nav-link href="{{ route('settings.sessions') }}" :active="request()->routeIs('settings.sessions')">Sessions</x-panel.nav-link>

                @if (config('panel.stripe.enabled') && filled(config('panel.stripe.secret')))
                    <x-panel.nav-link href="{{ route('billing.portal') }}">Billing</x-panel.nav-link>
                @endif

                @if (auth()->user()?->isAdmin())
                    <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">Admin</p>
                    <x-panel.nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.*')">Admin panel</x-panel.nav-link>
                @endif
            </nav>
            <div class="border-t border-border p-4">
                <p class="truncate text-sm font-medium text-zinc-200">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                <form method="post" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="text-xs text-ember-light hover:text-ember">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-border bg-surface-raised px-4 py-3 lg:px-8">
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500">{{ $section ?? 'Panel' }}</p>
                    <h1 class="text-lg font-semibold text-zinc-50">{{ $title ?? config('panel.name') }}</h1>
                </div>
                <a href="{{ route('servers.purchase') }}" class="panel-btn panel-btn-primary hidden sm:inline-flex">New server</a>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('status'))
                    <div class="panel-flash mb-6">{{ session('status') }}</div>
                @endif

                @if (session('daemon_token'))
                    <div class="panel-card mb-6">
                        <p class="font-medium text-zinc-200">Daemon token (copy now)</p>
                        <code class="mt-2 block break-all text-sm text-ember-light">{{ session('daemon_token') }}</code>
                        @if (session('hub_public_key'))
                            <p class="mt-4 font-medium text-zinc-200">Hub public key</p>
                            <code class="mt-2 block break-all text-sm text-ember-light">{{ session('hub_public_key') }}</code>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
