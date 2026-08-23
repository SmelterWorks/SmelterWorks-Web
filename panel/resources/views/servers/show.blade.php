@extends('layouts.app', ['title' => $server->name])

@section('content')
    <h1>{{ $server->name }}</h1>
    <div class="card">
        <p>Type: {{ $server->type }} · Status: {{ $server->status }}</p>
        <p><a href="{{ route('servers.mods', $server) }}">Mod browser</a> · <a href="{{ route('servers.files', $server) }}">Files</a></p>

        @if ($server->daemon)
            <p>
                Daemon: {{ $server->daemon->name }}
                ({{ $server->daemon->status }}
                @if ($server->daemon->isOnline())
                    · online
                @else
                    · offline
                @endif
                · last seen {{ $server->daemon->last_seen_at?->diffForHumans() ?? 'never' }})
            </p>
            @if ($server->daemon->metadata['container_status'] ?? null)
                <p>Container: {{ $server->daemon->metadata['container_status'] }}</p>
            @endif
            <div class="row">
                <form method="post" action="{{ route('servers.power', [$server, 'start']) }}">@csrf<button type="submit">Start</button></form>
                <form method="post" action="{{ route('servers.power', [$server, 'stop']) }}">@csrf<button type="submit">Stop</button></form>
                <form method="post" action="{{ route('servers.power', [$server, 'restart']) }}">@csrf<button type="submit">Restart</button></form>
                <form method="post" action="{{ route('servers.backup', $server) }}">@csrf<button type="submit">Backup</button></form>
            </div>
        @else
            <p>No daemon linked yet.</p>
            @if ($daemons->isNotEmpty())
                <form method="post" action="{{ route('servers.daemon.link', $server) }}">
                    @csrf
                    <label>Link paired daemon</label>
                    <select name="daemon_registration_id" required>
                        @foreach ($daemons as $daemon)
                            <option value="{{ $daemon->id }}">{{ $daemon->name }} ({{ $daemon->uuid }})</option>
                        @endforeach
                    </select>
                    <button type="submit">Link daemon</button>
                </form>
            @else
                <p><a href="{{ route('daemons.pairing') }}">Pair a daemon first</a></p>
            @endif
        @endif
    </div>

    @if ($server->daemon && $servers->isNotEmpty())
        <div class="card">
            <h2>Migrate world</h2>
            <form method="post" action="{{ route('servers.migrate', $server) }}">
                @csrf
                <label>Destination server</label>
                <select name="destination_server_id" required>
                    @foreach ($servers as $dest)
                        <option value="{{ $dest->id }}">{{ $dest->name }}</option>
                    @endforeach
                </select>
                <button type="submit">Queue migration</button>
            </form>
        </div>
    @endif

    <div class="card">
        <h2>Backups</h2>
        @forelse ($server->backups as $backup)
            <p>{{ $backup->uuid }} · {{ $backup->type }} · {{ $backup->status }}
                @if ($backup->local_path)
                    · {{ $backup->local_path }}
                @endif
            </p>
        @empty
            <p>No backups recorded yet.</p>
        @endforelse
    </div>
@endsection
