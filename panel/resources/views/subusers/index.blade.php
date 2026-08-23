@extends('layouts.app', ['title' => 'Subusers'])

@section('content')
    <h1>Subusers</h1>
    <div class="card">
        <form method="post" action="{{ route('subusers.store') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Permissions</label>
            @foreach ($permissions as $permission)
                <label><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ $permission }}</label>
            @endforeach
            <button type="submit">Invite or update</button>
        </form>
    </div>
    @foreach ($subusers as $subuser)
        <div class="card">
            <p>{{ $subuser->user->email }} · {{ implode(', ', $subuser->permissions ?? []) }}</p>
            <form method="post" action="{{ route('subusers.destroy', $subuser) }}">
                @csrf
                @method('DELETE')
                <button type="submit">Remove</button>
            </form>
        </div>
    @endforeach
@endsection
