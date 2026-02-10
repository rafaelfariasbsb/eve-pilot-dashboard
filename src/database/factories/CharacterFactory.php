<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterFactory extends Factory
{
    protected $model = Character::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'character_id' => fake()->unique()->numberBetween(90000000, 99999999),
            'name' => fake()->userName(),
            'corporation_id' => fake()->numberBetween(98000000, 98999999),
            'corporation_name' => fake()->company(),
            'alliance_id' => null,
            'alliance_name' => null,
            'portrait_url' => fake()->imageUrl(128, 128),
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addMinutes(20),
            'scopes' => ['esi-wallet.read_character_wallet.v1', 'esi-assets.read_assets.v1'],
            'is_main' => true,
            'wallet_balance' => fake()->randomFloat(2, 0, 10000000000),
            'last_synced_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'token_expires_at' => now()->subMinutes(5),
        ]);
    }

    public function alt(): static
    {
        return $this->state(fn() => [
            'is_main' => false,
        ]);
    }
}
