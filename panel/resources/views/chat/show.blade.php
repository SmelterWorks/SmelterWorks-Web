@extends('layouts.app', ['title' => $room->name, 'section' => 'Community'])

@section('content')
    <section class="panel-card flex h-[70vh] flex-col" id="chat-room" data-room-id="{{ $room->id }}" data-poll-url="{{ route('chat.poll', $room) }}" data-last-id="{{ $messages->last()?->id ?? 0 }}">
        <div class="flex-1 space-y-3 overflow-y-auto" id="chat-messages">
            @foreach ($messages as $message)
                <div class="rounded-lg border border-border bg-zinc-900/40 p-3" data-message-id="{{ $message->id }}">
                    <p class="text-xs text-zinc-500">{{ $message->user->name }} · {{ $message->created_at?->format('H:i') }}</p>
                    <p class="mt-1 text-sm text-zinc-200">{{ $message->body }}</p>
                </div>
            @endforeach
        </div>
        <form method="post" action="{{ route('chat.store', $room) }}" class="mt-4 flex gap-2">
            @csrf
            <input type="text" name="body" class="panel-input" placeholder="Message the room..." required autocomplete="off">
            <button type="submit" class="panel-btn panel-btn-primary shrink-0">Send</button>
        </form>
    </section>
@endsection
