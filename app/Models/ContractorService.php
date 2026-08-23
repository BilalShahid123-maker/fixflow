<?php

namespace App\Models;

use App\Enums\IssueCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorService extends Model
{
    protected $fillable = [
        'contractor_id',
        'trade',
        'base_cost_cents',
        'hourly_rate_cents',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'trade' => IssueCategory::class,
            'base_cost_cents' => 'integer',
            'hourly_rate_cents' => 'integer',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
