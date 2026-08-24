@extends('layouts.app', ['title' => $ticket->subject, 'section' => 'Administration'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel-card lg:col-span-2 space-y-3">
            @foreach ($ticket->messages as $message)
                <div class="rounded-lg border border-border p-3 {{ $message->is_staff ? 'bg-ember/10' : 'bg-zinc-900/40' }}">
                    <p class="text-xs text-zinc-500">{{ $message->user->name }} · {{ $message->created_at?->diffForHumans() }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-200">{{ $message->body }}</p>
                </div>
            @endforeach
            <form method="post" action="{{ route('admin.tickets.reply', $ticket) }}" class="space-y-3">
                @csrf
                <textarea name="body" rows="4" class="panel-input" required></textarea>
                <button type="submit" class="panel-btn panel-btn-primary">Staff reply</button>
            </form>
        </section>
        <aside class="panel-card h-fit space-y-4">
            <form method="post" action="{{ route('admin.tickets.update', $ticket) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="panel-label">Status</label>
                    <select name="status" class="panel-input">
                        <option value="open" @selected($ticket->status === 'open')>Open</option>
                        <option value="pending" @selected($ticket->status === 'pending')>Pending</option>
                        <option value="closed" @selected($ticket->status === 'closed')>Closed</option>
                    </select>
                </div>
                <div>
                    <label class="panel-label">Assignee</label>
                    <select name="assigned_admin_id" class="panel-input">
                        <option value="">Unassigned</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected($ticket->assigned_admin_id === $admin->id)>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="panel-btn panel-btn-ghost w-full">Update ticket</button>
            </form>
        </aside>
    </div>
@endsection
