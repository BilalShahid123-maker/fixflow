<?php

namespace App\Actions;

use App\Enums\RequestStatus;
use App\Jobs\ProcessMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use Illuminate\Support\Str;

class SubmitMaintenanceRequest
{
    /**
     * Create a maintenance request from a tenant submission and enqueue triage.
     *
     * @param  array{property_id: int, unit_id: int, tenant_name: string, tenant_email: string, title: string, description: string}  $data
     */
    public function execute(array $data): MaintenanceRequest
    {
        $tenant = Tenant::firstOrCreate(
            ['unit_id' => $data['unit_id'], 'email' => $data['tenant_email']],
            [
                'unit_id' => $data['unit_id'],
                'name' => $data['tenant_name'],
                'email' => $data['tenant_email'],
                'is_active' => true,
            ],
        );

        $request = MaintenanceRequest::create([
            'unit_id' => $data['unit_id'],
            'tenant_id' => $tenant->getKey(),
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => RequestStatus::PendingTriage,
            'reference' => Str::upper(Str::random(8)),
        ]);

        ProcessMaintenanceRequest::dispatch($request);

        return $request;
    }
}
