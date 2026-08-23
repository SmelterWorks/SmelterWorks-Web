<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('panel.name') }}</title>
    <style>
        :root { color-scheme: light dark; font-family: system-ui, sans-serif; }
        body { margin: 0; background: #111; color: #eee; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 1.5rem; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: .5rem; padding: 1rem; margin-bottom: 1rem; }
        label { display: block; margin: .75rem 0 .25rem; }
        input, select, button { width: 100%; padding: .6rem; border-radius: .35rem; border: 1px solid #444; background: #0f0f0f; color: #eee; }
        button { cursor: pointer; background: #c45c26; border-color: #c45c26; font-weight: 600; }
        .nav { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .nav a { color: #f0c090; }
        .error { color: #f88; }
        .flash { background: #243; padding: .75rem; border-radius: .35rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="wrap">
        @auth
            <nav class="nav">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('daemons.pairing') }}">BYOS pairing</a>
                <form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button type="submit" style="width:auto">Logout</button></form>
            </nav>
        @endauth
        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif
        @if (session('daemon_token'))
            <div class="card">
                <p><strong>Daemon token (copy now):</strong></p>
                <code>{{ session('daemon_token') }}</code>
            </div>
        @endif
        @yield('content')
    </div>
</body>
</html>
