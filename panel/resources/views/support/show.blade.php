@extends('layouts.app', ['title' => $ticket->subject, 'section' => 'Help'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel-card lg:col-span-2" id="ticket-thread" data-ticket-id="{{ $ticket->id }}" data-last-id="{{ $ticket->messages->last()?->id ?? 0 }}">
            <div class="mb-4 space-y-3" id="ticket-messages">
                @foreach ($ticket->messages as $message)
                    <div class="rounded-lg border border-border p-3 {{ $message->is_staff ? 'bg-ember/10' : 'bg-zinc-900/40' }}">
                        <p class="text-xs text-zinc-500">{{ $message->user->name }} · {{ $message->created_at?->diffForHumans() }}</p>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-200">{{ $message->body }}</p>
                    </div>
                @endforeach
            </div>
            <form method="post" action="{{ route('support.reply', $ticket) }}" class="space-y-3">
                @csrf
                <textarea name="body" rows="4" class="panel-input" placeholder="Write a reply..." required></textarea>
                <button type="submit" class="panel-btn panel-btn-primary">Send reply</button>
            </form>
        </section>
        <aside class="panel-card h-fit">
            <p class="text-sm text-zinc-400">Status</p>
            <p class="text-lg font-semibold text-zinc-100">{{ $ticket->status }}</p>
            <p class="mt-4 text-sm text-zinc-400">Priority</p>
            <p class="text-zinc-100">{{ $ticket->priority }}</p>
        </aside>
    </div>
@endsection
