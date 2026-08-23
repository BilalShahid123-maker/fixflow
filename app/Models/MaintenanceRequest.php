<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\RequestStatus;
use App\Enums\Severity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'unit_id',
        'tenant_id',
        'title',
        'description',
        'status',
        'category',
        'severity',
        'confidence',
        'emergency',
        'triaged_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'category' => IssueCategory::class,
            'severity' => Severity::class,
            'confidence' => 'float',
            'emergency' => 'boolean',
            'triaged_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MaintenanceAttachment::class);
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class);
    }

    public function latestAiRun(): ?AiRun
    {
        return $this->aiRuns()->latestOfMany();
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
