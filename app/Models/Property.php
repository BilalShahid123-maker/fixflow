<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'name',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'unit_count',
    ];

    protected function casts(): array
    {
        return [
            'unit_count' => 'integer',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
