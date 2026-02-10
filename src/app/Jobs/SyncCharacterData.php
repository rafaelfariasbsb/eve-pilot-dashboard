<?php

namespace App\Jobs;

use App\Models\Character;
use App\Models\CharacterAsset;
use App\Models\CharacterBlueprint;
use App\Services\EsiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCharacterData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $characterId,
    ) {}

    public function handle(EsiService $esi): void
    {
        $character = Character::where('character_id', $this->characterId)->first();
        if (!$character) {
            return;
        }

        Log::info("Syncing character data", ['character' => $character->name]);

        $this->syncWallet($esi, $character);
        $this->syncAssets($esi, $character);
        $this->syncBlueprints($esi, $character);

        $character->update(['last_synced_at' => now()]);

        Log::info("Character sync complete", ['character' => $character->name]);
    }

    private function syncWallet(EsiService $esi, Character $character): void
    {
        $balance = $esi->getWallet($character);
        if ($balance !== null) {
            $character->update(['wallet_balance' => $balance]);
        }
    }

    private function syncAssets(EsiService $esi, Character $character): void
    {
        $assets = $esi->getAssets($character);
        if ($assets === null) {
            return;
        }

        CharacterAsset::where('character_id', $character->character_id)->delete();

        $chunks = array_chunk($assets, 500);
        foreach ($chunks as $chunk) {
            $rows = array_map(fn($asset) => [
                'character_id' => $character->character_id,
                'item_id' => $asset['item_id'],
                'type_id' => $asset['type_id'],
                'location_id' => $asset['location_id'] ?? null,
                'location_type' => $asset['location_type'] ?? null,
                'quantity' => $asset['quantity'],
                'is_singleton' => $asset['is_singleton'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ], $chunk);

            CharacterAsset::insert($rows);
        }
    }

    private function syncBlueprints(EsiService $esi, Character $character): void
    {
        $blueprints = $esi->getBlueprints($character);
        if ($blueprints === null) {
            return;
        }

        CharacterBlueprint::where('character_id', $character->character_id)->delete();

        $chunks = array_chunk($blueprints, 500);
        foreach ($chunks as $chunk) {
            $rows = array_map(fn($bp) => [
                'character_id' => $character->character_id,
                'item_id' => $bp['item_id'],
                'type_id' => $bp['type_id'],
                'location_id' => $bp['location_id'] ?? null,
                'material_efficiency' => $bp['material_efficiency'] ?? 0,
                'time_efficiency' => $bp['time_efficiency'] ?? 0,
                'runs' => $bp['runs'] ?? -1,
                'quantity' => $bp['quantity'] ?? -1,
                'created_at' => now(),
                'updated_at' => now(),
            ], $chunk);

            CharacterBlueprint::insert($rows);
        }
    }
}
