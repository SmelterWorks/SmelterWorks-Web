@extends('layouts.app', ['title' => 'Support', 'section' => 'Help'])

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('support.create') }}" class="panel-btn panel-btn-primary">New ticket</a>
    </div>
    <div class="space-y-3">
        @forelse ($tickets as $ticket)
            <a href="{{ route('support.show', $ticket) }}" class="panel-list-item">
                <div>
                    <p class="font-medium text-zinc-100">{{ $ticket->subject }}</p>
                    <p class="text-xs text-zinc-500">{{ $ticket->status }} · {{ $ticket->updated_at?->diffForHumans() }}</p>
                </div>
                <span class="panel-badge">{{ $ticket->priority }}</span>
            </a>
        @empty
            <p class="text-sm text-zinc-500">No support tickets yet.</p>
        @endforelse
    </div>
@endsection
