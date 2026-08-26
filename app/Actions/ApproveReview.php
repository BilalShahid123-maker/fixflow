<?php

namespace App\Actions;

use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\DB;

class ApproveReview
{
    public function execute(MaintenanceRequest $request, int $userId): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $userId) {
            $request->forceFill(['status' => RequestStatus::Triaged])->save();

            AuditLog::record(
                event: 'review.approved',
                actorType: 'user',
                actorId: $userId,
                subject: $request,
            );

            return $request;
        });
    }
}
