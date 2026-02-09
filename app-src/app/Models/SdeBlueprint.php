<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SdeBlueprint extends Model
{
    protected $table = 'sde_blueprints';
    public $timestamps = false;

    protected $primaryKey = 'blueprint_type_id';
    public $incrementing = false;

    protected $fillable = [
        'blueprint_type_id',
        'max_production_limit',
    ];

    protected function casts(): array
    {
        return [
            'blueprint_type_id' => 'integer',
            'max_production_limit' => 'integer',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(SdeType::class, 'blueprint_type_id', 'type_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(SdeBlueprintMaterial::class, 'blueprint_type_id', 'blueprint_type_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SdeBlueprintProduct::class, 'blueprint_type_id', 'blueprint_type_id');
    }
}
