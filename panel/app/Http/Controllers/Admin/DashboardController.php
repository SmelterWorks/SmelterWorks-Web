<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameServer;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'users' => User::query()->count(),
            'organizations' => Organization::query()->count(),
            'servers' => GameServer::query()->count(),
            'openTickets' => SupportTicket::query()->where('status', SupportTicket::STATUS_OPEN)->count(),
            'recentEvents' => SecurityEvent::query()->latest()->limit(10)->get(),
        ]);
    }
}
