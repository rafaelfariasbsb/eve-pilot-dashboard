@extends('layouts.app')

@section('title', 'Blueprints')

@section('content')
<h2 class="mb-4">My Blueprints</h2>

@if($character)
    @if($blueprints->isEmpty())
        <div class="card p-5 text-center">
            <h5>No blueprints synced yet</h5>
            <p class="text-secondary">Click "Sync Character Data" on the dashboard to load your blueprints.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-eve">Go to Dashboard</a>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-dark table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Blueprint</th>
                            <th>Type</th>
                            <th class="text-center">ME</th>
                            <th class="text-center">TE</th>
                            <th class="text-center">Runs</th>
                            <th class="text-end">Est. Product Value</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($blueprints as $bp)
                        <tr>
                            <td>{{ $bp->type_name ?? 'Type #' . $bp->type_id }}</td>
                            <td>
                                @if($bp->isBpo())
                                    <span class="badge badge-bpo">BPO</span>
                                @elseif($bp->isBpc())
                                    <span class="badge badge-bpc">BPC</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $bp->material_efficiency }}%</td>
                            <td class="text-center">{{ $bp->time_efficiency }}%</td>
                            <td class="text-center">
                                {{ $bp->runs === -1 ? 'Unlimited' : $bp->runs }}
                            </td>
                            <td class="text-end text-isk">
                                @php
                                    $price = $marketPrices->get($bp->type_id);
                                @endphp
                                {{ $price ? number_format($price['average_price'] ?? 0, 2) . ' ISK' : '-' }}
                            </td>
                            <td>
                                <a href="{{ route('blueprints.show', $bp->type_id) }}"
                                   class="btn btn-outline-secondary btn-sm">
                                    Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $blueprints->links('pagination::bootstrap-5') }}
        </div>
    @endif
@else
    <div class="card p-5 text-center">
        <h4>No character linked</h4>
        <p class="text-secondary">Login with EVE Online to see your blueprints.</p>
    </div>
@endif
@endsection
