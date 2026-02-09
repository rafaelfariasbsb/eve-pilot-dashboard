@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
    <form action="{{ route('sync') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-eve btn-sm">Sync Character Data</button>
    </form>
</div>

@if($character)
<div class="row mb-4">
    {{-- Character Info Card --}}
    <div class="col-md-4">
        <div class="card p-3">
            <div class="d-flex align-items-center">
                @if($character->portrait_url)
                    <img src="{{ $character->portrait_url }}" width="80" height="80" class="portrait me-3">
                @endif
                <div>
                    <h5 class="mb-1">{{ $character->name }}</h5>
                    <p class="text-secondary mb-0">{{ $character->corporation_name ?? 'Unknown Corp' }}</p>
                    <small class="text-secondary">ID: {{ $character->character_id }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Wallet Card --}}
    <div class="col-md-4">
        <div class="card p-3">
            <h6 class="text-secondary">Wallet Balance</h6>
            <h3 class="text-isk mb-0">
                {{ number_format($walletBalance ?? $character->wallet_balance, 2) }} ISK
            </h3>
        </div>
    </div>

    {{-- Sync Status Card --}}
    <div class="col-md-4">
        <div class="card p-3">
            <h6 class="text-secondary">Sync Status</h6>
            <p class="mb-1">
                Assets: <strong>{{ $character->assets()->count() }}</strong> items
            </p>
            <p class="mb-1">
                Blueprints: <strong>{{ $character->blueprints()->count() }}</strong>
            </p>
            <small class="text-secondary">
                Last sync: {{ $character->last_synced_at?->diffForHumans() ?? 'Never' }}
            </small>
        </div>
    </div>
</div>

{{-- Recent Assets --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Top Assets (by quantity)</h5>
    </div>
    <div class="card-body p-0">
        @if($recentAssets->isEmpty())
            <div class="p-4 text-center text-secondary">
                No assets synced yet. Click "Sync Character Data" to load your assets.
            </div>
        @else
            <table class="table table-dark table-striped mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Quantity</th>
                        <th>Location Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAssets as $asset)
                    <tr>
                        <td>{{ $asset->type_name ?? 'Type #' . $asset->type_id }}</td>
                        <td class="text-end">{{ number_format($asset->quantity) }}</td>
                        <td><span class="badge bg-secondary">{{ $asset->location_type }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

{{-- Industry Jobs --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Active Industry Jobs</h5>
    </div>
    <div class="card-body p-0">
        @if(empty($industryJobs))
            <div class="p-4 text-center text-secondary">
                No active industry jobs found.
            </div>
        @else
            <table class="table table-dark table-striped mb-0">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Blueprint</th>
                        <th>Runs</th>
                        <th>Status</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($industryJobs as $job)
                    <tr>
                        <td>
                            @php
                                $activities = [1 => 'Manufacturing', 3 => 'TE Research', 4 => 'ME Research', 5 => 'Copying', 8 => 'Invention', 9 => 'Reaction'];
                            @endphp
                            {{ $activities[$job['activity_id']] ?? 'Unknown' }}
                        </td>
                        <td>Type #{{ $job['blueprint_type_id'] }}</td>
                        <td>{{ $job['runs'] }}</td>
                        <td>
                            <span class="badge {{ $job['status'] === 'active' ? 'bg-success' : 'bg-warning' }}">
                                {{ $job['status'] }}
                            </span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($job['end_date'])->format('Y-m-d H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@else
    <div class="card p-5 text-center">
        <h4>No character linked</h4>
        <p class="text-secondary">Login with EVE Online to see your character data.</p>
    </div>
@endif
@endsection
