<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $organization = $request->user()->organization;

        $rooms = ChatRoom::query()
            ->when(
                $organization !== null,
                fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('organization_id', $organization->id)
                    ->orWhere('is_public', true)),
                fn ($query) => $query->where('is_public', true),
            )
            ->orderBy('name')
            ->get();

        if ($rooms->isEmpty() && $organization !== null) {
            ChatRoom::query()->create([
                'organization_id' => $organization->id,
                'name' => $organization->name.' chat',
                'slug' => Str::slug($organization->name).'-'.Str::lower(Str::random(4)),
                'type' => 'team',
                'is_public' => false,
            ]);

            return redirect()->route('chat.index');
        }

        return view('chat.index', [
            'rooms' => $rooms,
        ]);
    }

    public function show(Request $request, ChatRoom $room): View
    {
        $this->authorizeRoom($request, $room);

        return view('chat.show', [
            'room' => $room,
            'messages' => $room->messages()->with('user')->latest()->limit(100)->get()->reverse()->values(),
        ]);
    }

    public function store(Request $request, ChatRoom $room): RedirectResponse
    {
        $this->authorizeRoom($request, $room);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ChatMessage::query()->create([
            'chat_room_id' => $room->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        event(new ChatMessageSent($message->load('user')));

        return back();
    }

    public function poll(Request $request, ChatRoom $room): JsonResponse
    {
        $this->authorizeRoom($request, $room);

        $afterId = (int) $request->query('after', 0);

        $messages = $room->messages()
            ->with('user')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'user' => $message->user?->only(['id', 'name']),
                'created_at' => $message->created_at?->toIso8601String(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    private function authorizeRoom(Request $request, ChatRoom $room): void
    {
        $user = $request->user();

        if ($room->is_public || $user->isAdmin()) {
            return;
        }

        abort_unless($user->organization_id === $room->organization_id, 403);
    }
}
