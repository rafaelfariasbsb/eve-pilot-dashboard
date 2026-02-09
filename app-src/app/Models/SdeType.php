<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdeType extends Model
{
    protected $table = 'sde_types';
    public $timestamps = false;

    protected $primaryKey = 'type_id';
    public $incrementing = false;

    protected $fillable = [
        'type_id',
        'group_id',
        'name',
        'description',
        'volume',
        'market_group_id',
        'published',
        'icon_id',
    ];

    protected function casts(): array
    {
        return [
            'type_id' => 'integer',
            'group_id' => 'integer',
            'volume' => 'decimal:4',
            'market_group_id' => 'integer',
            'published' => 'boolean',
            'icon_id' => 'integer',
        ];
    }
}
