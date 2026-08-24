@extends('layouts.app', ['title' => 'Team access', 'section' => 'Team'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel-card">
            <h2 class="mb-4 text-lg font-semibold text-zinc-50">Invite teammate</h2>
            <form method="post" action="{{ route('subusers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="panel-label">Email</label>
                    <input id="email" type="email" name="email" class="panel-input" required>
                </div>
                <fieldset>
                    <legend class="panel-label mb-2">Permissions</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-zinc-300">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-border">
                                <span>{{ str_replace('.', ' ', $permission) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <button type="submit" class="panel-btn panel-btn-primary">Save access</button>
            </form>
        </section>

        <section class="panel-card">
            <h2 class="mb-4 text-lg font-semibold text-zinc-50">Current access</h2>
            <div class="space-y-3">
                @forelse ($subusers as $subuser)
                    <div class="panel-list-item items-start">
                        <div>
                            <p class="font-medium text-zinc-100">{{ $subuser->user->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $subuser->user->email }}</p>
                            <p class="mt-2 text-xs text-zinc-400">{{ implode(', ', $subuser->permissions ?? []) }}</p>
                        </div>
                        <form method="post" action="{{ route('subusers.destroy', $subuser) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="panel-btn panel-btn-danger text-xs">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No subusers yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
