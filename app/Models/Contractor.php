<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'is_verified',
        'rating',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'rating' => 'float',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(ContractorService::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(ContractorAvailability::class);
    }

    public function openSlots()
    {
        return $this->availability()->where('is_booked', false)->where('starts_at', '>=', now());
    }
}
