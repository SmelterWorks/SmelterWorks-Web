<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('panel.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/auth.js'])
</head>
<body class="min-h-screen bg-surface text-zinc-100 antialiased">
    <div class="flex min-h-screen flex-col lg:flex-row">
        <aside class="relative flex flex-col justify-between border-b border-border bg-surface-raised px-8 py-10 lg:w-[42%] lg:border-b-0 lg:border-r lg:px-12 lg:py-14">
            <div>
                <a href="{{ route('login') }}" class="inline-flex no-underline">
                    <x-brand-logo :size="40" />
                </a>
                <p class="mt-10 max-w-md text-2xl font-semibold leading-snug text-zinc-50">
                    {{ $heroTitle ?? 'Run Vintage Story servers without the busywork.' }}
                </p>
                <p class="mt-4 max-w-md text-base leading-relaxed text-zinc-400">
                    {{ $heroCopy ?? 'Power controls, backups, file access, and BYOS daemon pairing in one place.' }}
                </p>
            </div>
            <p class="mt-10 text-sm text-zinc-500">
                SmelterWorks panel. No ads, no tracking.
            </p>
        </aside>

        <main class="flex flex-1 items-center justify-center px-6 py-10 lg:px-12">
            <div class="w-full max-w-md">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-emerald-800/60 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
