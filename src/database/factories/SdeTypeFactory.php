<?php

namespace Database\Factories;

use App\Models\SdeType;
use Illuminate\Database\Eloquent\Factories\Factory;

class SdeTypeFactory extends Factory
{
    protected $model = SdeType::class;

    public function definition(): array
    {
        return [
            'type_id' => fake()->unique()->numberBetween(1, 99999),
            'group_id' => fake()->numberBetween(1, 2000),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'volume' => fake()->randomFloat(4, 0.01, 50000),
            'market_group_id' => fake()->numberBetween(1, 2500),
            'published' => true,
            'icon_id' => fake()->numberBetween(1, 10000),
        ];
    }
}
