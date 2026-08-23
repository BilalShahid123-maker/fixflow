<?php

namespace App\Models;

use App\Enums\ActionStatus;
use App\Enums\AuthorityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiAction extends Model
{
    protected $fillable = [
        'ai_run_id',
        'action_type',
        'authority_level',
        'subject_type',
        'subject_id',
        'payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'authority_level' => AuthorityLevel::class,
            'payload' => 'array',
            'status' => ActionStatus::class,
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'ai_run_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function latestApproval(): ?Approval
    {
        return $this->approvals()->latestOfMany();
    }
}
