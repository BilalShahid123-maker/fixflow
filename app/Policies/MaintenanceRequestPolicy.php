<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    /**
     * Only an administrator can review or dispatch a request.
     */
    public function review(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin()
            && $request->status === RequestStatus::AwaitingApproval;
    }

    /**
     * Only an administrator can match a contractor to a triaged request.
     */
    public function dispatch(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin();
    }
}
