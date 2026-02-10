<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncCharacterData;
use App\Models\Character;
use App\Models\CharacterAsset;
use App\Models\CharacterBlueprint;
use App\Services\EsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncCharacterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_wallet_balance(): void
    {
        $character = Character::factory()->create(['wallet_balance' => 0]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(5000000.50);
        $esiMock->shouldReceive('getAssets')->andReturn(null);
        $esiMock->shouldReceive('getBlueprints')->andReturn(null);
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData($character->character_id);
        $job->handle($esiMock);

        $character->refresh();
        $this->assertEquals('5000000.50', $character->wallet_balance);
    }

    public function test_sync_replaces_assets(): void
    {
        $character = Character::factory()->create();

        // Create old assets that should be deleted
        CharacterAsset::factory()->count(2)->create([
            'character_id' => $character->character_id,
        ]);

        $newAssets = [
            ['item_id' => 1001, 'type_id' => 34, 'location_id' => 60000001, 'location_type' => 'station', 'quantity' => 500, 'is_singleton' => false],
            ['item_id' => 1002, 'type_id' => 35, 'location_id' => 60000001, 'location_type' => 'station', 'quantity' => 200, 'is_singleton' => false],
            ['item_id' => 1003, 'type_id' => 36, 'location_id' => 60000001, 'location_type' => 'station', 'quantity' => 100, 'is_singleton' => false],
        ];

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(null);
        $esiMock->shouldReceive('getAssets')->andReturn($newAssets);
        $esiMock->shouldReceive('getBlueprints')->andReturn(null);
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData($character->character_id);
        $job->handle($esiMock);

        $assets = CharacterAsset::where('character_id', $character->character_id)->get();
        $this->assertCount(3, $assets);
        $this->assertEquals(1001, $assets->where('type_id', 34)->first()->item_id);
    }

    public function test_sync_replaces_blueprints(): void
    {
        $character = Character::factory()->create();

        CharacterBlueprint::factory()->create([
            'character_id' => $character->character_id,
        ]);

        $newBlueprints = [
            ['item_id' => 2001, 'type_id' => 1000, 'location_id' => 60000001, 'material_efficiency' => 10, 'time_efficiency' => 20, 'runs' => -1, 'quantity' => -1],
        ];

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(null);
        $esiMock->shouldReceive('getAssets')->andReturn(null);
        $esiMock->shouldReceive('getBlueprints')->andReturn($newBlueprints);
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData($character->character_id);
        $job->handle($esiMock);

        $bps = CharacterBlueprint::where('character_id', $character->character_id)->get();
        $this->assertCount(1, $bps);
        $this->assertEquals(2001, $bps->first()->item_id);
        $this->assertEquals(10, $bps->first()->material_efficiency);
    }

    public function test_sync_updates_last_synced_at(): void
    {
        $character = Character::factory()->create(['last_synced_at' => null]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(null);
        $esiMock->shouldReceive('getAssets')->andReturn(null);
        $esiMock->shouldReceive('getBlueprints')->andReturn(null);
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData($character->character_id);
        $job->handle($esiMock);

        $character->refresh();
        $this->assertNotNull($character->last_synced_at);
    }

    public function test_sync_skips_when_character_not_found(): void
    {
        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldNotReceive('getWallet');
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData(99999999);
        $job->handle($esiMock);

        // No exception thrown = passes
        $this->assertTrue(true);
    }

    public function test_sync_handles_null_wallet(): void
    {
        $character = Character::factory()->create(['wallet_balance' => 1000.00]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(null);
        $esiMock->shouldReceive('getAssets')->andReturn(null);
        $esiMock->shouldReceive('getBlueprints')->andReturn(null);
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData($character->character_id);
        $job->handle($esiMock);

        $character->refresh();
        $this->assertEquals('1000.00', $character->wallet_balance);
    }

    public function test_sync_handles_null_assets(): void
    {
        $character = Character::factory()->create();
        CharacterAsset::factory()->count(3)->create([
            'character_id' => $character->character_id,
        ]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(null);
        $esiMock->shouldReceive('getAssets')->andReturn(null);
        $esiMock->shouldReceive('getBlueprints')->andReturn(null);
        $this->app->instance(EsiService::class, $esiMock);

        $job = new SyncCharacterData($character->character_id);
        $job->handle($esiMock);

        // Assets should NOT be deleted when API returns null
        $this->assertCount(3, CharacterAsset::where('character_id', $character->character_id)->get());
    }
}
