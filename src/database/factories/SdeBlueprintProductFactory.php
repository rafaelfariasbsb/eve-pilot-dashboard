<?php

namespace Database\Factories;

use App\Models\SdeBlueprintProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class SdeBlueprintProductFactory extends Factory
{
    protected $model = SdeBlueprintProduct::class;

    public function definition(): array
    {
        return [
            'blueprint_type_id' => fake()->numberBetween(1, 99999),
            'activity' => 'manufacturing',
            'product_type_id' => fake()->numberBetween(1, 99999),
            'quantity' => 1,
            'probability' => null,
        ];
    }
}
