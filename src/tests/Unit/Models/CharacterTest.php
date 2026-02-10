<?php

namespace Tests\Unit\Models;

use App\Models\Character;
use App\Models\CharacterAsset;
use App\Models\CharacterBlueprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_token_expired_returns_true_when_null(): void
    {
        $character = Character::factory()->create([
            'token_expires_at' => null,
        ]);

        $this->assertTrue($character->isTokenExpired());
    }

    public function test_is_token_expired_returns_true_when_past(): void
    {
        $character = Character::factory()->create([
            'token_expires_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue($character->isTokenExpired());
    }

    public function test_is_token_expired_returns_false_when_future(): void
    {
        $character = Character::factory()->create([
            'token_expires_at' => now()->addMinutes(20),
        ]);

        $this->assertFalse($character->isTokenExpired());
    }

    public function test_user_relationship(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $character->user);
        $this->assertEquals($user->id, $character->user->id);
    }

    public function test_assets_relationship(): void
    {
        $character = Character::factory()->create();
        CharacterAsset::factory()->count(3)->create([
            'character_id' => $character->character_id,
        ]);

        $this->assertCount(3, $character->assets);
        $this->assertInstanceOf(CharacterAsset::class, $character->assets->first());
    }

    public function test_blueprints_relationship(): void
    {
        $character = Character::factory()->create();
        CharacterBlueprint::factory()->count(2)->create([
            'character_id' => $character->character_id,
        ]);

        $this->assertCount(2, $character->blueprints);
        $this->assertInstanceOf(CharacterBlueprint::class, $character->blueprints->first());
    }
}
