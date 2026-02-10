<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterAsset extends Model
{
    use HasFactory;
    protected $fillable = [
        'character_id',
        'item_id',
        'type_id',
        'location_id',
        'location_type',
        'quantity',
        'is_singleton',
    ];

    protected function casts(): array
    {
        return [
            'character_id' => 'integer',
            'item_id' => 'integer',
            'type_id' => 'integer',
            'location_id' => 'integer',
            'quantity' => 'integer',
            'is_singleton' => 'boolean',
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
}
