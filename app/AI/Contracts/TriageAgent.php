<?php

namespace App\AI\Contracts;

use App\AI\Dto\TriageResult;
use App\Models\MaintenanceRequest;

interface TriageAgent
{
    public function triage(MaintenanceRequest $request): TriageResult;
}
