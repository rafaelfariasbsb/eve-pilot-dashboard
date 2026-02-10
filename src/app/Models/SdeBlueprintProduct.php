<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdeBlueprintProduct extends Model
{
    use HasFactory;
    protected $table = 'sde_blueprint_products';
    public $timestamps = false;

    protected $fillable = [
        'blueprint_type_id',
        'activity',
        'product_type_id',
        'quantity',
        'probability',
    ];

    protected function casts(): array
    {
        return [
            'blueprint_type_id' => 'integer',
            'product_type_id' => 'integer',
            'quantity' => 'integer',
            'probability' => 'decimal:4',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(SdeBlueprint::class, 'blueprint_type_id', 'blueprint_type_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(SdeType::class, 'product_type_id', 'type_id');
    }
}
