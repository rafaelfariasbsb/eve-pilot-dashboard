<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\EsiService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(EsiService $esi): View
    {
        $user = auth()->user();
        $character = $user->mainCharacter();

        $walletBalance = null;
        $recentAssets = collect();
        $industryJobs = [];

        if ($character) {
            $walletBalance = $esi->getWallet($character);
            if ($walletBalance !== null) {
                $character->update(['wallet_balance' => $walletBalance]);
            }

            $recentAssets = $character->assets()
                ->join('sde_types', 'character_assets.type_id', '=', 'sde_types.type_id')
                ->select('character_assets.*', 'sde_types.name as type_name')
                ->orderByDesc('character_assets.quantity')
                ->limit(20)
                ->get();

            $industryJobs = $esi->getIndustryJobs($character) ?? [];
        }

        return view('dashboard.index', compact(
            'character',
            'walletBalance',
            'recentAssets',
            'industryJobs'
        ));
    }
}
