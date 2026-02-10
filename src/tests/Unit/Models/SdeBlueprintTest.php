<?php

namespace Tests\Unit\Models;

use App\Models\SdeBlueprint;
use App\Models\SdeBlueprintMaterial;
use App\Models\SdeBlueprintProduct;
use App\Models\SdeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SdeBlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_relationship(): void
    {
        $type = SdeType::factory()->create();
        $blueprint = SdeBlueprint::factory()->create([
            'blueprint_type_id' => $type->type_id,
        ]);

        $this->assertInstanceOf(SdeType::class, $blueprint->type);
        $this->assertEquals($type->type_id, $blueprint->type->type_id);
    }

    public function test_materials_relationship(): void
    {
        $blueprint = SdeBlueprint::factory()->create();
        SdeBlueprintMaterial::factory()->count(3)->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
        ]);

        $this->assertCount(3, $blueprint->materials);
        $this->assertInstanceOf(SdeBlueprintMaterial::class, $blueprint->materials->first());
    }

    public function test_products_relationship(): void
    {
        $blueprint = SdeBlueprint::factory()->create();
        SdeBlueprintProduct::factory()->count(2)->create([
            'blueprint_type_id' => $blueprint->blueprint_type_id,
        ]);

        $this->assertCount(2, $blueprint->products);
        $this->assertInstanceOf(SdeBlueprintProduct::class, $blueprint->products->first());
    }
}
