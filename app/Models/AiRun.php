<?php

namespace App\Models;

use App\Enums\AuthorityLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiRun extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'agent',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'latency_ms',
        'cost_usd',
        'status',
        'error',
        'input_payload',
        'output',
    ];

    protected function casts(): array
    {
        return [
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'latency_ms' => 'integer',
            'cost_usd' => 'float',
            'input_payload' => 'array',
            'output' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AiAction::class);
    }

    public function markSucceeded(array $output, int $inputTokens = 0, int $outputTokens = 0, ?int $latencyMs = null, ?float $costUsd = null): void
    {
        $this->update([
            'status' => 'succeeded',
            'output' => $output,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'latency_ms' => $latencyMs,
            'cost_usd' => $costUsd,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
        ]);
    }
}
