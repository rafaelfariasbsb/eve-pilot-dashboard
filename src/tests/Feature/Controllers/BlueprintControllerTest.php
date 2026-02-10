<?php

namespace Tests\Feature\Controllers;

use App\Models\Character;
use App\Models\CharacterBlueprint;
use App\Models\SdeBlueprint;
use App\Models\SdeBlueprintMaterial;
use App\Models\SdeBlueprintProduct;
use App\Models\SdeType;
use App\Models\User;
use App\Services\EsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BlueprintControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_blueprints_index_requires_authentication(): void
    {
        $response = $this->get(route('blueprints.index'));

        // App has no named 'login' route, so auth middleware returns an error
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_blueprints_index_lists_character_blueprints(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $type = SdeType::factory()->create(['name' => 'Rifter Blueprint']);
        CharacterBlueprint::factory()->create([
            'character_id' => $character->character_id,
            'type_id' => $type->type_id,
        ]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getMarketPrices')->andReturn([]);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('blueprints.index'));

        $response->assertStatus(200);
        $response->assertViewIs('blueprints.index');
        $response->assertViewHas('blueprints');
    }

    public function test_blueprints_show_displays_manufacturing_details(): void
    {
        $blueprintType = SdeType::factory()->create(['name' => 'Rifter Blueprint']);
        $productType = SdeType::factory()->create(['name' => 'Rifter']);
        $materialType = SdeType::factory()->create(['name' => 'Tritanium']);

        $blueprint = SdeBlueprint::factory()->create([
            'blueprint_type_id' => $blueprintType->type_id,
        ]);

        SdeBlueprintMaterial::factory()->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
            'activity' => 'manufacturing',
            'material_type_id' => $materialType->type_id,
            'quantity' => 100,
        ]);

        SdeBlueprintProduct::factory()->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
            'activity' => 'manufacturing',
            'product_type_id' => $productType->type_id,
            'quantity' => 1,
        ]);

        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getMarketPrices')->andReturn([
            ['type_id' => $materialType->type_id, 'average_price' => 5.0],
            ['type_id' => $productType->type_id, 'average_price' => 1000.0],
        ]);
        $this->app->instance(EsiService::class, $esiMock);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('blueprints.show', $blueprintType->type_id));

        $response->assertStatus(200);
        $response->assertViewIs('blueprints.show');
        $response->assertViewHas('blueprint');
        $response->assertViewHas('manufacturingMaterials');
        $response->assertViewHas('products');
    }

    public function test_blueprints_show_calculates_profit_correctly(): void
    {
        $blueprintType = SdeType::factory()->create();
        $productType = SdeType::factory()->create();
        $mat1Type = SdeType::factory()->create();
        $mat2Type = SdeType::factory()->create();

        $blueprint = SdeBlueprint::factory()->create([
            'blueprint_type_id' => $blueprintType->type_id,
        ]);

        SdeBlueprintMaterial::factory()->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
            'activity' => 'manufacturing',
            'material_type_id' => $mat1Type->type_id,
            'quantity' => 100,
        ]);
        SdeBlueprintMaterial::factory()->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
            'activity' => 'manufacturing',
            'material_type_id' => $mat2Type->type_id,
            'quantity' => 50,
        ]);

        SdeBlueprintProduct::factory()->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
            'activity' => 'manufacturing',
            'product_type_id' => $productType->type_id,
            'quantity' => 1,
        ]);

        // mat1: 100 * 10.0 = 1000, mat2: 50 * 20.0 = 1000, total = 2000
        // product: 1 * 5000.0 = 5000
        // profit = 5000 - 2000 = 3000
        $esiMock = Mockery::mock(EsiService::class);
        $esiMock->shouldReceive('getMarketPrices')->andReturn([
            ['type_id' => $mat1Type->type_id, 'average_price' => 10.0],
            ['type_id' => $mat2Type->type_id, 'average_price' => 20.0],
            ['type_id' => $productType->type_id, 'average_price' => 5000.0],
        ]);
        $this->app->instance(EsiService::class, $esiMock);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('blueprints.show', $blueprintType->type_id));

        $response->assertViewHas('totalMaterialCost', 2000.0);
        $response->assertViewHas('productPrice', 5000.0);
        $response->assertViewHas('estimatedProfit', 3000.0);
    }

    public function test_blueprints_show_404_for_nonexistent_blueprint(): void
    {
        $user = User::factory()->create();

        $esiMock = Mockery::mock(EsiService::class);
        $this->app->instance(EsiService::class, $esiMock);

        $response = $this->actingAs($user)->get(route('blueprints.show', 99999));

        $response->assertStatus(404);
    }
}
