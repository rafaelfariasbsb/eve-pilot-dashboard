@extends('layouts.app')

@section('title', 'Blueprint Details')

@section('content')
<div class="mb-3">
    <a href="{{ route('blueprints.index') }}" class="text-secondary">&larr; Back to Blueprints</a>
</div>

<h2 class="mb-4">{{ $blueprint->type->name ?? 'Blueprint #' . $blueprint->blueprint_type_id }}</h2>

<div class="row">
    {{-- Manufacturing Materials --}}
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Manufacturing Materials</h5>
            </div>
            <div class="card-body p-0">
                @if($manufacturingMaterials->isEmpty())
                    <div class="p-4 text-center text-secondary">No manufacturing data in SDE</div>
                @else
                    <table class="table table-dark table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($manufacturingMaterials as $mat)
                            <tr>
                                <td>{{ $mat->materialType->name ?? 'Type #' . $mat->material_type_id }}</td>
                                <td class="text-end">{{ number_format($mat->quantity) }}</td>
                                <td class="text-end text-isk">{{ number_format($mat->unit_price, 2) }}</td>
                                <td class="text-end text-isk">{{ number_format($mat->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Total Material Cost:</td>
                                <td class="text-end text-isk">{{ number_format($totalMaterialCost, 2) }} ISK</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Cost Summary --}}
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Cost Summary</h5>
            </div>
            <div class="card-body">
                @php $product = $products->first(); @endphp

                <div class="mb-3">
                    <small class="text-secondary">Product</small>
                    <p class="mb-0 fw-bold">
                        {{ $product?->productType?->name ?? 'Unknown' }}
                        @if($product && $product->quantity > 1)
                            <span class="text-secondary">x{{ $product->quantity }}</span>
                        @endif
                    </p>
                </div>

                <div class="mb-3">
                    <small class="text-secondary">Material Cost (base)</small>
                    <p class="text-isk mb-0">{{ number_format($totalMaterialCost, 2) }} ISK</p>
                </div>

                <div class="mb-3">
                    <small class="text-secondary">Product Market Value</small>
                    <p class="text-isk mb-0">{{ number_format($productPrice, 2) }} ISK</p>
                </div>

                <hr class="border-secondary">

                <div>
                    <small class="text-secondary">Estimated Profit (per run)</small>
                    <h4 class="{{ $estimatedProfit >= 0 ? 'text-eve-green' : 'text-eve-red' }}">
                        {{ number_format($estimatedProfit, 2) }} ISK
                    </h4>
                    <small class="text-secondary">
                        * Based on average market prices. Does not include job cost, taxes, ME/TE bonuses.
                    </small>
                </div>
            </div>
        </div>

        {{-- Blueprint Info --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Blueprint Info</h5>
            </div>
            <div class="card-body">
                <p><strong>Type ID:</strong> {{ $blueprint->blueprint_type_id }}</p>
                <p><strong>Max Production Limit:</strong> {{ $blueprint->max_production_limit }}</p>
                <p><strong>Activities:</strong></p>
                <ul class="text-secondary">
                    @if($blueprint->materials->where('activity', 'manufacturing')->count())
                        <li>Manufacturing</li>
                    @endif
                    @if($blueprint->materials->where('activity', 'invention')->count())
                        <li>Invention</li>
                    @endif
                    @if($blueprint->materials->where('activity', 'copying')->count())
                        <li>Copying</li>
                    @endif
                    @if($blueprint->materials->where('activity', 'reaction')->count())
                        <li>Reaction</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
