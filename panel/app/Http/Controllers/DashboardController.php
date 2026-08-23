<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $organization = $user?->organization;

        return view('dashboard', [
            'servers' => $organization?->gameServers()->latest()->get() ?? collect(),
            'daemons' => $organization?->daemons()->latest()->get() ?? collect(),
        ]);
    }
}
