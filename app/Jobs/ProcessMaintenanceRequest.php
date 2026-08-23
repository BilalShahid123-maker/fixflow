<?php

namespace App\Jobs;

use App\AI\Contracts\TriageAgent;
use App\Enums\RequestStatus;
use App\Models\AiRun;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMaintenanceRequest implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(public MaintenanceRequest $request) {}

    public function uniqueId(): string
    {
        return (string) $this->request->getKey();
    }

    public function cacheUniqueIdLockTTL(): int
    {
        return 300;
    }

    public function handle(TriageAgent $agent): void
    {
        $request = $this->request->fresh();

        if (! $request || $request->status !== RequestStatus::PendingTriage) {
            return;
        }

        $run = AiRun::create([
            'maintenance_request_id' => $request->getKey(),
            'agent' => $agent::class,
            'provider' => config('fixflow.triage.driver'),
            'status' => 'running',
            'input_payload' => [
                'title' => $request->title,
                'description' => $request->description,
                'unit_label' => $request->unit?->label,
            ],
        ]);

        try {
            $startedAt = hrtime(true);

            $result = $agent->triage($request);

            $latencyMs = (int) ((hrtime(true) - $startedAt) / 1e6);

            $request->forceFill([
                'category' => $result->category,
                'severity' => $result->severity,
                'confidence' => $result->confidence,
                'emergency' => $result->isEmergency,
                'triaged_at' => now(),
                'status' => $this->targetStatus($result->confidence),
            ])->save();

            $run->markSucceeded(
                output: $result->toArray(),
                latencyMs: $latencyMs,
            );

            AuditLog::record(
                event: 'ai.triage.completed',
                actorType: 'agent',
                subject: $request,
                properties: [
                    'ai_run_id' => $run->getKey(),
                    ...$result->toArray(),
                    'routed_to' => $request->refresh()->status->value,
                ],
            );
        } catch (\Throwable $e) {
            $run->markFailed($e->getMessage());

            AuditLog::record(
                event: 'ai.triage.failed',
                actorType: 'system',
                subject: $request,
                properties: ['ai_run_id' => $run->getKey(), 'error' => $e->getMessage()],
            );

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $request = $this->request->fresh();

        if (! $request) {
            return;
        }

        $request->forceFill(['status' => RequestStatus::PendingTriage])->save();

        AuditLog::record(
            event: 'ai.triage.exhausted_retries',
            actorType: 'system',
            subject: $request,
            properties: ['error' => $e->getMessage()],
        );
    }

    private function targetStatus(float $confidence): RequestStatus
    {
        return $confidence >= (float) config('fixflow.triage.confidence.auto_route_min')
            ? RequestStatus::Triaged
            : RequestStatus::AwaitingApproval;
    }
}
