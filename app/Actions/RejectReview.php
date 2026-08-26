<?php

namespace App\Actions;

use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\DB;

class RejectReview
{
    public function execute(MaintenanceRequest $request, int $userId, ?string $note = null): MaintenanceRequest
    {
        return DB::transaction(function () use ($request, $userId, $note) {
            $request->forceFill(['status' => RequestStatus::Rejected])->save();

            AuditLog::record(
                event: 'review.rejected',
                actorType: 'user',
                actorId: $userId,
                subject: $request,
                properties: $note !== null ? ['note' => $note] : [],
            );

            return $request;
        });
    }
}
