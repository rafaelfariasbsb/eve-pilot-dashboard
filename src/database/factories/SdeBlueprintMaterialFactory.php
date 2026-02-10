<?php

namespace Database\Factories;

use App\Models\SdeBlueprintMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class SdeBlueprintMaterialFactory extends Factory
{
    protected $model = SdeBlueprintMaterial::class;

    public function definition(): array
    {
        return [
            'blueprint_type_id' => fake()->numberBetween(1, 99999),
            'activity' => 'manufacturing',
            'material_type_id' => fake()->numberBetween(1, 99999),
            'quantity' => fake()->numberBetween(1, 10000),
        ];
    }
}
