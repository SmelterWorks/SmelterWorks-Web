@extends('layouts.app', ['title' => 'Mods'])

@section('content')
    <h1>Mods for {{ $server->name }}</h1>
    <form method="get">
        <input type="search" name="q" value="{{ $query }}" placeholder="Search VS ModDB">
        <button type="submit">Search</button>
    </form>
    @foreach ($mods as $mod)
        <div class="card">
            <strong>{{ $mod['name'] ?? 'Mod' }}</strong>
            <form method="post" action="{{ route('servers.mods.install', $server) }}">
                @csrf
                <input type="hidden" name="modid" value="{{ $mod['modid'] ?? '' }}">
                <input type="hidden" name="name" value="{{ $mod['name'] ?? 'Mod' }}">
                <button type="submit">Install</button>
            </form>
        </div>
    @endforeach
@endsection
