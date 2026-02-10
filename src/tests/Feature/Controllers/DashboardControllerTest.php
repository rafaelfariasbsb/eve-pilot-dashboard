<?php

namespace Tests\Feature\Controllers;

use App\Models\Character;
use App\Models\CharacterAsset;
use App\Models\SdeType;
use App\Models\User;
use App\Services\EsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        // App has no named 'login' route, so auth middleware returns an error
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_dashboard_displays_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(1000000.50);
        $esiMock->shouldReceive('getIndustryJobs')->andReturn([]);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_dashboard_shows_wallet_balance(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(5000000.75);
        $esiMock->shouldReceive('getIndustryJobs')->andReturn([]);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertViewHas('walletBalance', 5000000.75);
    }

    public function test_dashboard_shows_assets_with_type_names(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $type = SdeType::factory()->create(['name' => 'Tritanium']);
        CharacterAsset::factory()->create([
            'character_id' => $character->character_id,
            'type_id' => $type->type_id,
            'quantity' => 5000,
        ]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getWallet')->andReturn(100.00);
        $esiMock->shouldReceive('getIndustryJobs')->andReturn([]);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertViewHas('recentAssets');
        $assets = $response->viewData('recentAssets');
        $this->assertNotEmpty($assets);
        $this->assertEquals('Tritanium', $assets->first()->type_name);
    }

    public function test_dashboard_handles_no_character(): void
    {
        $user = User::factory()->create();

        $esiMock = Mockery::mock(EsiService::class);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('walletBalance', null);
    }
}
