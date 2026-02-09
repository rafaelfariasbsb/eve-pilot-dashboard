<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterBlueprint extends Model
{
    protected $fillable = [
        'character_id',
        'item_id',
        'type_id',
        'location_id',
        'material_efficiency',
        'time_efficiency',
        'runs',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'character_id' => 'integer',
            'item_id' => 'integer',
            'type_id' => 'integer',
            'location_id' => 'integer',
            'material_efficiency' => 'integer',
            'time_efficiency' => 'integer',
            'runs' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id', 'character_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(SdeType::class, 'type_id', 'type_id');
    }

    public function isBpo(): bool
    {
        return $this->quantity === -1;
    }

    public function isBpc(): bool
    {
        return $this->quantity === -2;
    }
}
