<?php

namespace App\Http\Controllers;

use App\Events\TicketMessageSent;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('support.index', [
            'tickets' => SupportTicket::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('support.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $ticket = SupportTicket::query()->create([
            'user_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $message = SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_staff' => false,
        ]);

        event(new TicketMessageSent($message));

        return redirect()->route('support.show', $ticket)->with('status', 'Ticket created.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return view('support.show', [
            'ticket' => $ticket->load('messages.user'),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_staff' => $request->user()->isAdmin(),
        ]);

        event(new TicketMessageSent($message));

        return back()->with('status', 'Message sent.');
    }

    public function poll(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $afterId = (int) $request->query('after', 0);

        $messages = $ticket->messages()
            ->with('user')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (SupportTicketMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'is_staff' => $message->is_staff,
                'user' => $message->user?->only(['id', 'name']),
                'created_at' => $message->created_at?->toIso8601String(),
            ]);

        return response()->json(['messages' => $messages]);
    }
}
