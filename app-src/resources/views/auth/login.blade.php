@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card text-center p-5">
            <h1 class="mb-3">EVE Pilot Dashboard</h1>
            <p class="text-secondary mb-4">
                A pilot project to test EVE Online ESI API connections.<br>
                Login with your EVE character to see your data.
            </p>

            <div class="mb-4">
                <a href="{{ route('eve.login') }}" class="btn btn-eve btn-lg px-5">
                    Login with EVE Online
                </a>
            </div>

            <hr class="border-secondary">

            <div class="text-start mt-3">
                <h6>This app requests the following permissions:</h6>
                <ul class="text-secondary small">
                    <li>Read wallet balance</li>
                    <li>Read character assets</li>
                    <li>Read character blueprints</li>
                    <li>Read industry jobs</li>
                    <li>Read skills</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
