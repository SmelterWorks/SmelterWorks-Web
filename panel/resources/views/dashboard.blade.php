@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <h1>{{ config('panel.name') }}</h1>
    <div class="card">
        <h2>Servers</h2>
        @forelse ($servers as $server)
            <p><a href="{{ route('servers.show', $server) }}">{{ $server->name }}</a> ({{ $server->type }})</p>
        @empty
            <p>No servers yet.</p>
        @endforelse
        <form method="post" action="{{ route('servers.store') }}">
            @csrf
            <input type="hidden" name="type" value="byos">
            <label>Server name</label>
            <input type="text" name="name" required>
            <button type="submit">Add BYOS server</button>
        </form>
    </div>
    <div class="card">
        <h2>Daemons</h2>
        @forelse ($daemons as $daemon)
            <p>{{ $daemon->name }} · {{ $daemon->status }} · last seen {{ $daemon->last_seen_at?->diffForHumans() ?? 'never' }}</p>
        @empty
            <p>No daemons paired yet.</p>
        @endforelse
    </div>
@endsection
