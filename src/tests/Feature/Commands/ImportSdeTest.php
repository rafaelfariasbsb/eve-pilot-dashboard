<?php

namespace Tests\Feature\Commands;

use App\Models\SdeBlueprint;
use App\Models\SdeBlueprintMaterial;
use App\Models\SdeBlueprintProduct;
use App\Models\SdeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportSdeTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_sde_downloads_and_imports_types(): void
    {
        $typesCsv = "typeID,groupID,typeName,description,mass,volume,capacity,portionSize,marketGroupID,iconID,soundID,graphicID,published\n";
        $typesCsv .= "34,18,Tritanium,\"The main mineral\",0,0.01,0,100,18,,,,1\n";
        $typesCsv .= "35,18,Pyerite,\"A common mineral\",0,0.01,0,100,18,,,,1\n";

        Http::fake([
            '*invTypes.csv' => Http::response($typesCsv, 200),
            '*industryActivity.csv' => Http::response("typeID,activityID,time\n", 200),
            '*industryActivityMaterials.csv' => Http::response("typeID,activityID,materialTypeID,quantity\n", 200),
            '*industryActivityProducts.csv' => Http::response("typeID,activityID,productTypeID,quantity,probability\n", 200),
        ]);

        $this->artisan('eve:import-sde')
            ->assertExitCode(0);

        $this->assertEquals(2, SdeType::count());
        $this->assertDatabaseHas('sde_types', ['type_id' => 34, 'name' => 'Tritanium']);
        $this->assertDatabaseHas('sde_types', ['type_id' => 35, 'name' => 'Pyerite']);
    }

    public function test_import_sde_imports_blueprints_manufacturing_only(): void
    {
        $typesCsv = "typeID,groupID,typeName,description,mass,volume,capacity,portionSize,marketGroupID,iconID,soundID,graphicID,published\n";

        $blueprintsCsv = "typeID,activityID,time\n";
        $blueprintsCsv .= "1000,1,3600\n";  // manufacturing
        $blueprintsCsv .= "1000,3,1800\n";  // research_time (same blueprint, different activity)
        $blueprintsCsv .= "2000,1,7200\n";  // manufacturing
        $blueprintsCsv .= "3000,5,900\n";   // copying only (should NOT create blueprint)

        Http::fake([
            '*invTypes.csv' => Http::response($typesCsv, 200),
            '*industryActivity.csv' => Http::response($blueprintsCsv, 200),
            '*industryActivityMaterials.csv' => Http::response("typeID,activityID,materialTypeID,quantity\n", 200),
            '*industryActivityProducts.csv' => Http::response("typeID,activityID,productTypeID,quantity,probability\n", 200),
        ]);

        $this->artisan('eve:import-sde')
            ->assertExitCode(0);

        // Only blueprints with activityId=1 (manufacturing) should be imported
        $this->assertEquals(2, SdeBlueprint::count());
        $this->assertDatabaseHas('sde_blueprints', ['blueprint_type_id' => 1000]);
        $this->assertDatabaseHas('sde_blueprints', ['blueprint_type_id' => 2000]);
        $this->assertDatabaseMissing('sde_blueprints', ['blueprint_type_id' => 3000]);
    }

    public function test_import_sde_imports_materials_with_activity_mapping(): void
    {
        $typesCsv = "typeID,groupID,typeName,description,mass,volume,capacity,portionSize,marketGroupID,iconID,soundID,graphicID,published\n";
        $blueprintsCsv = "typeID,activityID,time\n";

        $materialsCsv = "typeID,activityID,materialTypeID,quantity\n";
        $materialsCsv .= "1000,1,34,100\n";   // manufacturing
        $materialsCsv .= "1000,8,35,5\n";     // invention

        Http::fake([
            '*invTypes.csv' => Http::response($typesCsv, 200),
            '*industryActivity.csv' => Http::response($blueprintsCsv, 200),
            '*industryActivityMaterials.csv' => Http::response($materialsCsv, 200),
            '*industryActivityProducts.csv' => Http::response("typeID,activityID,productTypeID,quantity,probability\n", 200),
        ]);

        $this->artisan('eve:import-sde')
            ->assertExitCode(0);

        $this->assertEquals(2, SdeBlueprintMaterial::count());
        $this->assertDatabaseHas('sde_blueprint_materials', [
            'blueprint_type_id' => 1000,
            'activity' => 'manufacturing',
            'material_type_id' => 34,
            'quantity' => 100,
        ]);
        $this->assertDatabaseHas('sde_blueprint_materials', [
            'blueprint_type_id' => 1000,
            'activity' => 'invention',
            'material_type_id' => 35,
        ]);
    }

    public function test_import_sde_imports_products(): void
    {
        $typesCsv = "typeID,groupID,typeName,description,mass,volume,capacity,portionSize,marketGroupID,iconID,soundID,graphicID,published\n";
        $blueprintsCsv = "typeID,activityID,time\n";
        $materialsCsv = "typeID,activityID,materialTypeID,quantity\n";

        $productsCsv = "typeID,activityID,productTypeID,quantity,probability\n";
        $productsCsv .= "1000,1,2000,1,\n";        // manufacturing product
        $productsCsv .= "1000,8,3000,1,0.3400\n";  // invention product with probability

        Http::fake([
            '*invTypes.csv' => Http::response($typesCsv, 200),
            '*industryActivity.csv' => Http::response($blueprintsCsv, 200),
            '*industryActivityMaterials.csv' => Http::response($materialsCsv, 200),
            '*industryActivityProducts.csv' => Http::response($productsCsv, 200),
        ]);

        $this->artisan('eve:import-sde')
            ->assertExitCode(0);

        $this->assertEquals(2, SdeBlueprintProduct::count());
        $this->assertDatabaseHas('sde_blueprint_products', [
            'blueprint_type_id' => 1000,
            'activity' => 'manufacturing',
            'product_type_id' => 2000,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('sde_blueprint_products', [
            'blueprint_type_id' => 1000,
            'activity' => 'invention',
            'product_type_id' => 3000,
        ]);
    }

    public function test_import_sde_handles_download_failure(): void
    {
        Http::fake([
            '*invTypes.csv' => Http::response('Server Error', 500),
            '*industryActivity.csv' => Http::response('Server Error', 500),
            '*industryActivityMaterials.csv' => Http::response('Server Error', 500),
            '*industryActivityProducts.csv' => Http::response('Server Error', 500),
        ]);

        $this->artisan('eve:import-sde')
            ->assertExitCode(0);

        $this->assertEquals(0, SdeType::count());
        $this->assertEquals(0, SdeBlueprint::count());
    }
}
