@extends('layouts.app', ['title' => 'Admin', 'section' => 'Administration'])

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="panel-card"><p class="text-sm text-zinc-500">Users</p><p class="text-3xl font-semibold">{{ $users }}</p></div>
        <div class="panel-card"><p class="text-sm text-zinc-500">Organizations</p><p class="text-3xl font-semibold">{{ $organizations }}</p></div>
        <div class="panel-card"><p class="text-sm text-zinc-500">Servers</p><p class="text-3xl font-semibold">{{ $servers }}</p></div>
        <div class="panel-card"><p class="text-sm text-zinc-500">Open tickets</p><p class="text-3xl font-semibold">{{ $openTickets }}</p></div>
    </div>

    <section class="panel-card mt-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-50">Recent security events</h2>
            <a href="{{ route('admin.tickets.index') }}" class="panel-btn panel-btn-ghost text-sm">Support tickets</a>
        </div>
        <div class="space-y-2 text-sm">
            @forelse ($recentEvents as $event)
                <div class="panel-list-item">
                    <span class="text-zinc-300">{{ $event->event }}</span>
                    <span class="text-xs text-zinc-500">{{ $event->created_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-zinc-500">No events logged.</p>
            @endforelse
        </div>
    </section>
@endsection
