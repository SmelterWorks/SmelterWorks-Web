@extends('layouts.app', ['title' => 'Sessions', 'section' => 'Account'])

@section('content')
    <section class="panel-card">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-50">Active sessions</h2>
            <form method="post" action="{{ route('settings.sessions.destroy-others') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="panel-btn panel-btn-ghost text-sm">Sign out other devices</button>
            </form>
        </div>
        <div class="space-y-3">
            @forelse ($sessions as $session)
                <div class="panel-list-item">
                    <div>
                        <p class="font-medium text-zinc-100">
                            {{ $session->user_agent_family ?? 'Unknown browser' }}
                            @if ($session->session_id === $currentSessionId)
                                <span class="panel-badge ml-2">Current</span>
                            @endif
                        </p>
                        <p class="text-xs text-zinc-500">IP subnet {{ $session->ip_subnet }} · {{ $session->last_activity_at?->diffForHumans() }}</p>
                    </div>
                    @if ($session->session_id !== $currentSessionId)
                        <form method="post" action="{{ route('settings.sessions.destroy', $session) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="panel-btn panel-btn-ghost text-xs">Revoke</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-zinc-500">No tracked sessions yet.</p>
            @endforelse
        </div>
    </section>
@endsection
