<?php

namespace Database\Factories;

use App\Models\CharacterBlueprint;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterBlueprintFactory extends Factory
{
    protected $model = CharacterBlueprint::class;

    public function definition(): array
    {
        return [
            'character_id' => fake()->numberBetween(90000000, 99999999),
            'item_id' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'type_id' => fake()->numberBetween(1, 50000),
            'location_id' => fake()->numberBetween(60000000, 60999999),
            'material_efficiency' => fake()->numberBetween(0, 10),
            'time_efficiency' => fake()->numberBetween(0, 20),
            'runs' => -1,
            'quantity' => -1,
        ];
    }

    public function bpo(): static
    {
        return $this->state(fn() => ['quantity' => -1]);
    }

    public function bpc(): static
    {
        return $this->state(fn() => [
            'quantity' => -2,
            'runs' => fake()->numberBetween(1, 100),
        ]);
    }
}
