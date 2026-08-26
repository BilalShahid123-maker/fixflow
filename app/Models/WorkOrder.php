<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'contractor_id',
        'status',
        'estimated_cost_cents',
        'final_cost_cents',
        'scheduled_for',
        'scheduled_until',
        'approved_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'estimated_cost_cents' => 'integer',
            'final_cost_cents' => 'integer',
            'scheduled_for' => 'datetime',
            'scheduled_until' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
