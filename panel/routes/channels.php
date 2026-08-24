<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ticket.{ticketId}', function (User $user, int $ticketId): bool {
    if ($user->isAdmin()) {
        return true;
    }

    return $user->supportTickets()->whereKey($ticketId)->exists();
});
