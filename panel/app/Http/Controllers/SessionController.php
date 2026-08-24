<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        $currentId = $request->session()->getId();

        $sessions = UserSession::query()
            ->where('user_id', $request->user()->id)
            ->where('revoked', false)
            ->orderByDesc('last_activity_at')
            ->get();

        $laravelSessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get();

        return view('settings.sessions', [
            'sessions' => $sessions,
            'laravelSessions' => $laravelSessions,
            'currentSessionId' => $currentId,
        ]);
    }

    public function destroy(Request $request, UserSession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $session->update(['revoked' => true]);

        DB::table('sessions')->where('id', $session->session_id)->delete();

        return back()->with('status', 'Session revoked.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $currentId = $request->session()->getId();

        UserSession::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', '!=', $currentId)
            ->update(['revoked' => true]);

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $currentId)
            ->delete();

        return back()->with('status', 'Other sessions signed out.');
    }
}
