@extends('layouts.app', ['title' => 'Support tickets', 'section' => 'Administration'])

@section('content')
    <div class="space-y-3">
        @foreach ($tickets as $ticket)
            <a href="{{ route('admin.tickets.show', $ticket) }}" class="panel-list-item">
                <div>
                    <p class="font-medium text-zinc-100">{{ $ticket->subject }}</p>
                    <p class="text-xs text-zinc-500">{{ $ticket->user->name }} · {{ $ticket->status }}</p>
                </div>
                <span class="panel-badge">{{ $ticket->priority }}</span>
            </a>
        @endforeach
    </div>
@endsection
