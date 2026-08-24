<?php

namespace App\Http\Controllers\Admin;

use App\Events\TicketMessageSent;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        return view('admin.tickets.index', [
            'tickets' => SupportTicket::query()->with(['user', 'organization'])->latest()->get(),
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        return view('admin.tickets.show', [
            'ticket' => $ticket->load(['messages.user', 'user', 'organization']),
            'admins' => User::query()->where('is_admin', true)->get(),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,pending,closed'],
            'assigned_admin_id' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'assigned_admin_id' => $validated['assigned_admin_id'],
            'closed_at' => $validated['status'] === SupportTicket::STATUS_CLOSED ? now() : null,
        ]);

        return back()->with('status', 'Ticket updated.');
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_staff' => true,
        ]);

        event(new TicketMessageSent($message));

        if ($ticket->status === SupportTicket::STATUS_OPEN) {
            $ticket->update(['status' => SupportTicket::STATUS_PENDING]);
        }

        return back()->with('status', 'Reply sent.');
    }
}
