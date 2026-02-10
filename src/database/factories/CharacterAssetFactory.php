<?php

namespace Database\Factories;

use App\Models\CharacterAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterAssetFactory extends Factory
{
    protected $model = CharacterAsset::class;

    public function definition(): array
    {
        return [
            'character_id' => fake()->numberBetween(90000000, 99999999),
            'item_id' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'type_id' => fake()->numberBetween(1, 50000),
            'location_id' => fake()->numberBetween(60000000, 60999999),
            'location_type' => 'station',
            'quantity' => fake()->numberBetween(1, 10000),
            'is_singleton' => false,
        ];
    }
}
