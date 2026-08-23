<?php

namespace App\AI\Permissions;

use App\AI\Dto\DispatchProposal;
use App\Enums\ActionStatus;
use App\Enums\AuthorityLevel;
use App\Enums\Severity;

class PermissionGate
{
    public function evaluate(DispatchProposal $proposal): PermissionDecision
    {
        $reasons = [];

        $autoRouteMin = (float) config('fixflow.triage.confidence.auto_route_min');
        $costLimit = (int) config('fixflow.dispatch.auto_execute_cost_limit_cents');

        if ($proposal->confidence < $autoRouteMin) {
            $reasons[] = sprintf('confidence %.2f is below auto-route minimum %.2f', $proposal->confidence, $autoRouteMin);
        }

        if ($proposal->estimatedCostCents > $costLimit) {
            $reasons[] = sprintf(
                'estimated cost %d cents exceeds auto-execute limit %d cents',
                $proposal->estimatedCostCents,
                $costLimit,
            );
        }

        if (config('fixflow.dispatch.require_verified_contractor') && $proposal->contractorVerified === false) {
            $reasons[] = 'contractor is not verified';
        }

        if ($proposal->severity->atLeast(Severity::Critical)) {
            $reasons[] = sprintf('severity "%s" always requires human approval', $proposal->severity->value);
        }

        if ($reasons !== []) {
            return new PermissionDecision(
                proposal: $proposal,
                grantedLevel: AuthorityLevel::Prepare,
                status: ActionStatus::NeedsApproval,
                reasons: $reasons,
            );
        }

        return new PermissionDecision(
            proposal: $proposal,
            grantedLevel: $proposal->requestedLevel,
            status: ActionStatus::Approved,
            reasons: ['all auto-execute rules satisfied'],
        );
    }
}
