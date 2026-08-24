@extends('layouts.app', ['title' => 'Chat', 'section' => 'Community'])

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($rooms as $room)
            <a href="{{ route('chat.show', $room) }}" class="panel-card block no-underline transition hover:border-ember/40">
                <h3 class="font-semibold text-zinc-100">{{ $room->name }}</h3>
                <p class="mt-1 text-xs text-zinc-500">{{ $room->type }} · {{ $room->is_public ? 'public' : 'team' }}</p>
            </a>
        @endforeach
    </div>
@endsection
