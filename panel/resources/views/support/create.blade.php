@extends('layouts.app', ['title' => 'New ticket', 'section' => 'Help'])

@section('content')
    <section class="panel-card max-w-2xl">
        <form method="post" action="{{ route('support.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="subject" class="panel-label">Subject</label>
                <input id="subject" type="text" name="subject" class="panel-input" required>
            </div>
            <div>
                <label for="priority" class="panel-label">Priority</label>
                <select id="priority" name="priority" class="panel-input">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div>
                <label for="body" class="panel-label">Message</label>
                <textarea id="body" name="body" rows="6" class="panel-input" required></textarea>
            </div>
            <button type="submit" class="panel-btn panel-btn-primary">Submit ticket</button>
        </form>
    </section>
@endsection
