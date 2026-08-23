@extends('layouts.app', ['title' => 'BYOS pairing'])

@section('content')
    <h1>BYOS daemon pairing</h1>
    <div class="card">
        <p>Install <code>smelterd</code> on your host, then paste the registration token into your daemon config.</p>
        <p>When the panel or internet is down, use <code>daemon-tool</code> on the host over the local socket.</p>
        <form method="post" action="{{ route('daemons.store') }}">
            @csrf
            <label>Daemon name</label>
            <input type="text" name="name" required>
            <button type="submit">Create registration token</button>
        </form>
    </div>
    @foreach ($daemons as $daemon)
        <div class="card">
            <p>{{ $daemon->name }} · {{ $daemon->status }} · {{ $daemon->uuid }}</p>
            <p>Last seen: {{ $daemon->last_seen_at?->diffForHumans() ?? 'never' }}
                @if ($daemon->isOnline())
                    · online
                @endif
            </p>
            @if ($daemon->gameServer)
                <p>Linked server: <a href="{{ route('servers.show', $daemon->gameServer) }}">{{ $daemon->gameServer->name }}</a></p>
            @endif
        </div>
    @endforeach
@endsection
