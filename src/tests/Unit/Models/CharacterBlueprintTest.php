<?php

namespace Tests\Unit\Models;

use App\Models\Character;
use App\Models\CharacterBlueprint;
use App\Models\SdeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterBlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_bpo_returns_true_when_quantity_is_minus_one(): void
    {
        $blueprint = CharacterBlueprint::factory()->bpo()->create();

        $this->assertTrue($blueprint->isBpo());
    }

    public function test_is_bpo_returns_false_for_other_quantities(): void
    {
        $blueprint = CharacterBlueprint::factory()->bpc()->create();

        $this->assertFalse($blueprint->isBpo());
    }

    public function test_is_bpc_returns_true_when_quantity_is_minus_two(): void
    {
        $blueprint = CharacterBlueprint::factory()->bpc()->create();

        $this->assertTrue($blueprint->isBpc());
    }

    public function test_is_bpc_returns_false_for_other_quantities(): void
    {
        $blueprint = CharacterBlueprint::factory()->bpo()->create();

        $this->assertFalse($blueprint->isBpc());
    }

    public function test_character_relationship(): void
    {
        $character = Character::factory()->create();
        $blueprint = CharacterBlueprint::factory()->create([
            'character_id' => $character->character_id,
        ]);

        $this->assertInstanceOf(Character::class, $blueprint->character);
        $this->assertEquals($character->character_id, $blueprint->character->character_id);
    }

    public function test_type_relationship(): void
    {
        $type = SdeType::factory()->create();
        $blueprint = CharacterBlueprint::factory()->create([
            'type_id' => $type->type_id,
        ]);

        $this->assertInstanceOf(SdeType::class, $blueprint->type);
        $this->assertEquals($type->type_id, $blueprint->type->type_id);
    }
}
