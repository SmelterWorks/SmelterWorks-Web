@extends('layouts.app', ['title' => 'Files'])

@section('content')
    <h1>Files for {{ $server->name }}</h1>
    <div class="card">
        <p>Path: <code>{{ $path === '' ? '/' : $path }}</code></p>
        <form method="post" action="{{ route('servers.files.list', $server) }}">
            @csrf
            <input type="hidden" name="path" value="{{ $path }}">
            <button type="submit">Refresh listing</button>
        </form>
        <form method="post" action="{{ route('servers.files.upload', $server) }}">
            @csrf
            <label>File path</label>
            <input type="text" name="path" value="{{ $path }}" required>
            <label>Content</label>
            <textarea name="content" rows="12" required></textarea>
            <button type="submit">Save file</button>
        </form>
        <p><a href="{{ route('servers.show', $server) }}">Back to server</a></p>
    </div>
@endsection
