<?php

namespace App\Actions;

use App\Enums\RequestStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AuditLog;
use App\Models\WorkOrder;

class ApproveWorkOrder
{
    public function execute(WorkOrder $workOrder, int $userId): WorkOrder
    {
        $workOrder->update([
            'status' => WorkOrderStatus::Scheduled,
            'approved_by' => $userId,
        ]);

        $request = $workOrder->request;

        if ($request !== null) {
            $request->update(['status' => RequestStatus::Dispatched]);
        }

        AuditLog::record(
            event: 'dispatch.approved',
            actorType: 'user',
            subject: $workOrder->request,
            properties: [
                'work_order_id' => $workOrder->getKey(),
                'contractor_id' => $workOrder->contractor_id,
                'scheduled_for' => $workOrder->scheduled_for?->toIso8601String(),
                'approved_by' => $userId,
            ],
        );

        return $workOrder->fresh();
    }
}
