<?php

namespace App\Http\Controllers;

use App\Models\SdeBlueprint;
use App\Services\EsiService;
use Illuminate\View\View;

class BlueprintController extends Controller
{
    public function index(EsiService $esi): View
    {
        $user = auth()->user();
        $character = $user->mainCharacter();

        $blueprints = collect();
        $marketPrices = collect();

        if ($character) {
            $blueprints = $character->blueprints()
                ->join('sde_types', 'character_blueprints.type_id', '=', 'sde_types.type_id')
                ->select('character_blueprints.*', 'sde_types.name as type_name')
                ->orderBy('sde_types.name')
                ->paginate(25);

            $rawPrices = $esi->getMarketPrices();
            if ($rawPrices) {
                $marketPrices = collect($rawPrices)->keyBy('type_id');
            }
        }

        return view('blueprints.index', compact('character', 'blueprints', 'marketPrices'));
    }

    public function show(int $typeId, EsiService $esi): View
    {
        $blueprint = SdeBlueprint::with(['type', 'materials.materialType', 'products.productType'])
            ->where('blueprint_type_id', $typeId)
            ->firstOrFail();

        $manufacturingMaterials = $blueprint->materials
            ->where('activity', 'manufacturing');

        $products = $blueprint->products
            ->where('activity', 'manufacturing');

        $rawPrices = $esi->getMarketPrices();
        $marketPrices = collect($rawPrices ?? [])->keyBy('type_id');

        $totalMaterialCost = 0;
        foreach ($manufacturingMaterials as $mat) {
            $price = $marketPrices->get($mat->material_type_id);
            $mat->unit_price = $price['average_price'] ?? 0;
            $mat->total_price = $mat->unit_price * $mat->quantity;
            $totalMaterialCost += $mat->total_price;
        }

        $productPrice = 0;
        $product = $products->first();
        if ($product) {
            $price = $marketPrices->get($product->product_type_id);
            $productPrice = ($price['average_price'] ?? 0) * $product->quantity;
        }

        $estimatedProfit = $productPrice - $totalMaterialCost;

        return view('blueprints.show', compact(
            'blueprint',
            'manufacturingMaterials',
            'products',
            'totalMaterialCost',
            'productPrice',
            'estimatedProfit'
        ));
    }
}
