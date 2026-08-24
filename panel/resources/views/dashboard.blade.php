@extends('layouts.app', ['title' => 'Dashboard', 'section' => 'Overview'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel-card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-50">Your servers</h2>
                <a href="{{ route('servers.purchase') }}" class="panel-btn panel-btn-ghost text-sm">Add server</a>
            </div>
            <div class="space-y-3">
                @forelse ($servers as $server)
                    <a href="{{ route('servers.show', $server) }}" class="panel-list-item">
                        <div>
                            <p class="font-medium text-zinc-100">{{ $server->name }}</p>
                            <p class="text-xs text-zinc-500">{{ strtoupper($server->type) }} · {{ $server->status }}</p>
                        </div>
                        <span class="panel-badge">{{ $server->plan_slug ?? 'custom' }}</span>
                    </a>
                @empty
                    <p class="text-sm text-zinc-500">No servers yet. <a href="{{ route('servers.purchase') }}" class="text-ember-light">Get started</a>.</p>
                @endforelse
            </div>
        </section>

        <section class="panel-card">
            <h2 class="mb-4 text-lg font-semibold text-zinc-50">Daemons</h2>
            <div class="space-y-3">
                @forelse ($daemons as $daemon)
                    <div class="panel-list-item">
                        <div>
                            <p class="font-medium text-zinc-100">{{ $daemon->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $daemon->status }} · last seen {{ $daemon->last_seen_at?->diffForHumans() ?? 'never' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No daemons paired. <a href="{{ route('daemons.pairing') }}" class="text-ember-light">Pair one</a>.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
