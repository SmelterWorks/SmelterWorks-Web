@extends('layouts.app', ['title' => $server->name])

@section('content')
    <h1>{{ $server->name }}</h1>
    <div class="card">
        <p>Type: {{ $server->type }} · Status: {{ $server->status }}</p>
        <p><a href="{{ route('servers.mods', $server) }}">Mod browser</a></p>
        @if ($server->daemon)
            <p>Daemon: {{ $server->daemon->name }} ({{ $server->daemon->status }})</p>
        @endif
        <h2>Backups</h2>
        @forelse ($server->backups as $backup)
            <p>{{ $backup->uuid }} · {{ $backup->type }} · {{ $backup->status }}</p>
        @empty
            <p>No backups recorded yet.</p>
        @endforelse
    </div>
@endsection
