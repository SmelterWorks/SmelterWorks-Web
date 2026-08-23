@extends('layouts.app', ['title' => 'Billing'])

@section('content')
    <h1>Billing updated</h1>
    <div class="card">
        <p>Your subscription checkout completed. It may take a minute to show on the server record.</p>
        <p><a href="{{ route('dashboard') }}">Back to dashboard</a></p>
    </div>
@endsection
