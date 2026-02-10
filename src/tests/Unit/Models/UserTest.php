<?php

namespace Tests\Unit\Models;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_characters_relationship(): void
    {
        $user = User::factory()->create();
        Character::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->characters);
        $this->assertInstanceOf(Character::class, $user->characters->first());
    }

    public function test_main_character_returns_main(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id, 'is_main' => false]);
        $main = Character::factory()->create(['user_id' => $user->id, 'is_main' => true]);

        $result = $user->mainCharacter();

        $this->assertNotNull($result);
        $this->assertEquals($main->id, $result->id);
    }

    public function test_main_character_returns_null_when_none(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id, 'is_main' => false]);

        $this->assertNull($user->mainCharacter());
    }
}
