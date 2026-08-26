<?php

namespace App\Actions;

use App\AI\Matching\ContractorMatcher;
use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;

class MatchContractor
{
    public function __construct(
        private ContractorMatcher $matcher,
    ) {}

    public function execute(MaintenanceRequest $request): ?WorkOrder
    {
        $request = $request->fresh();

        if ($request === null || $request->status !== RequestStatus::Triaged) {
            return null;
        }

        $category = $request->category;

        if ($category === null) {
            return null;
        }

        $result = $this->matcher->find($category);

        if ($result === null) {
            AuditLog::record(
                event: 'dispatch.match_failed',
                actorType: 'system',
                subject: $request,
                properties: ['category' => $category->value, 'reason' => 'no_matching_contractor'],
            );

            return null;
        }

        $estimatedCostCents = $result->service?->base_cost_cents ?? 0;

        $workOrder = WorkOrder::create([
            'maintenance_request_id' => $request->getKey(),
            'contractor_id' => $result->contractor->getKey(),
            'status' => 'draft',
            'estimated_cost_cents' => $estimatedCostCents,
            'scheduled_for' => $result->nextSlot?->starts_at,
            'scheduled_until' => $result->nextSlot?->ends_at,
        ]);

        $request->update(['status' => RequestStatus::Dispatching]);

        AuditLog::record(
            event: 'dispatch.matched',
            actorType: 'system',
            subject: $request,
            properties: [
                'work_order_id' => $workOrder->getKey(),
                'contractor_id' => $result->contractor->getKey(),
                'contractor' => $result->contractor->name,
                'score' => $result->score,
                'estimated_cost_cents' => $estimatedCostCents,
            ],
        );

        return $workOrder;
    }
}
