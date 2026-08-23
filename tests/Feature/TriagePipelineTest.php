<?php

namespace Tests\Feature;

use App\AI\Dto\DispatchProposal;
use App\AI\Permissions\PermissionGate;
use App\Enums\ActionStatus;
use App\Enums\AuthorityLevel;
use App\Enums\RequestStatus;
use App\Enums\Severity;
use App\Jobs\ProcessMaintenanceRequest;
use App\Models\AiRun;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriagePipelineTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(string $title, string $description): MaintenanceRequest
    {
        $property = Property::create([
            'name' => 'Maple Court',
            'street' => '12 Maple St',
            'city' => 'Springfield',
            'unit_count' => 1,
        ]);

        $unit = Unit::create([
            'property_id' => $property->getKey(),
            'label' => '302',
        ]);

        return MaintenanceRequest::create([
            'unit_id' => $unit->getKey(),
            'title' => $title,
            'description' => $description,
            'status' => RequestStatus::PendingTriage,
        ]);
    }

    public function test_triages_clear_plumbing_leak_and_routes_it_automatically(): void
    {
        $request = $this->makeRequest(
            'Water leaking under sink',
            'There is a leak coming from the pipe under the kitchen sink. Water is pooling in the cabinet.',
        );

        ProcessMaintenanceRequest::dispatchSync($request);

        $request->refresh();

        $this->assertSame(RequestStatus::Triaged, $request->status);
        $this->assertSame('plumbing', $request->category->value);
        $this->assertSame('high', $request->severity->value);
        $this->assertGreaterThanOrEqual(0.9, $request->confidence);
        $this->assertNotNull($request->triaged_at);

        $run = AiRun::first();
        $this->assertSame(1, AiRun::count());
        $this->assertSame('succeeded', $run->status);
        $this->assertNotNull($run->latency_ms);
        $this->assertSame(1, AuditLog::where('event', 'ai.triage.completed')->count());
    }

    public function test_routes_low_confidence_requests_to_human_review(): void
    {
        $request = $this->makeRequest(
            'Something smells weird',
            'I noticed a strange smell near the hallway but I am not sure what it is or where it comes from.',
        );

        ProcessMaintenanceRequest::dispatchSync($request);

        $request->refresh();

        $this->assertSame(RequestStatus::AwaitingApproval, $request->status);
        $this->assertLessThan(0.7, $request->confidence);
        $this->assertStringContainsString('keywords matched', AiRun::first()->output['reasoning']);
    }

    public function test_skips_requests_that_were_already_triaged(): void
    {
        $request = $this->makeRequest(
            'Water leaking under sink',
            'Leak under the sink from the drain pipe.',
        );

        ProcessMaintenanceRequest::dispatchSync($request);

        $request->update(['description' => 'changed after triage']);

        ProcessMaintenanceRequest::dispatchSync($request->fresh());

        $this->assertSame(1, AiRun::count());
    }

    public function test_grants_execute_authority_only_when_all_auto_execute_rules_pass(): void
    {
        $gate = new PermissionGate;

        $allowed = $gate->evaluate(new DispatchProposal(
            actionType: 'dispatch_contractor',
            estimatedCostCents: 25000,
            severity: Severity::Medium,
            confidence: 0.94,
            contractorVerified: true,
        ));

        $this->assertTrue($allowed->mayAutoExecute());
        $this->assertSame(ActionStatus::Approved, $allowed->status);
        $this->assertSame(AuthorityLevel::Execute, $allowed->grantedLevel);

        $expensive = $gate->evaluate(new DispatchProposal(
            actionType: 'dispatch_contractor',
            estimatedCostCents: 200000,
            severity: Severity::High,
            confidence: 0.95,
            contractorVerified: true,
        ));

        $this->assertFalse($expensive->mayAutoExecute());
        $this->assertSame(ActionStatus::NeedsApproval, $expensive->status);
        $this->assertStringContainsString('exceeds auto-execute limit', $expensive->reasons[0]);

        $critical = $gate->evaluate(new DispatchProposal(
            actionType: 'dispatch_contractor',
            estimatedCostCents: 10000,
            severity: Severity::Critical,
            confidence: 0.99,
            contractorVerified: true,
        ));

        $this->assertFalse($critical->mayAutoExecute());
        $this->assertStringContainsString('human approval', implode(' ', $critical->reasons));

        $unverified = $gate->evaluate(new DispatchProposal(
            actionType: 'dispatch_contractor',
            estimatedCostCents: 10000,
            severity: Severity::Low,
            confidence: 0.99,
            contractorVerified: false,
        ));

        $this->assertFalse($unverified->mayAutoExecute());
    }
}
