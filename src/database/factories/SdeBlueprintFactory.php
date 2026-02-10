<?php

namespace Database\Factories;

use App\Models\SdeBlueprint;
use Illuminate\Database\Eloquent\Factories\Factory;

class SdeBlueprintFactory extends Factory
{
    protected $model = SdeBlueprint::class;

    public function definition(): array
    {
        return [
            'blueprint_type_id' => fake()->unique()->numberBetween(1, 99999),
            'max_production_limit' => fake()->numberBetween(1, 300),
        ];
    }
}
