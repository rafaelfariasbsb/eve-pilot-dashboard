<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'character_id',
        'name',
        'corporation_id',
        'corporation_name',
        'alliance_id',
        'alliance_name',
        'portrait_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'is_main',
        'wallet_balance',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'character_id' => 'integer',
            'corporation_id' => 'integer',
            'alliance_id' => 'integer',
            'token_expires_at' => 'datetime',
            'is_main' => 'boolean',
            'wallet_balance' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'scopes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(CharacterAsset::class, 'character_id', 'character_id');
    }

    public function blueprints(): HasMany
    {
        return $this->hasMany(CharacterBlueprint::class, 'character_id', 'character_id');
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at === null || $this->token_expires_at->isPast();
    }
}
