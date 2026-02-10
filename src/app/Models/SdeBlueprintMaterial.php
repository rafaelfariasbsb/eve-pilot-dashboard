<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdeBlueprintMaterial extends Model
{
    use HasFactory;
    protected $table = 'sde_blueprint_materials';
    public $timestamps = false;

    protected $fillable = [
        'blueprint_type_id',
        'activity',
        'material_type_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'blueprint_type_id' => 'integer',
            'material_type_id' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(SdeBlueprint::class, 'blueprint_type_id', 'blueprint_type_id');
    }

    public function materialType(): BelongsTo
    {
        return $this->belongsTo(SdeType::class, 'material_type_id', 'type_id');
    }
}
