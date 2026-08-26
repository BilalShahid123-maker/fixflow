<?php

namespace Tests\Feature;

use App\Actions\ApproveWorkOrder;
use App\Actions\MatchContractor;
use App\AI\Matching\ContractorMatcher;
use App\Enums\IssueCategory;
use App\Enums\RequestStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Contractor;
use App\Models\ContractorAvailability;
use App\Models\ContractorService;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorMatchTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedPlumber(): Contractor
    {
        $contractor = Contractor::create([
            'name' => 'Test Plumber',
            'is_verified' => true,
            'rating' => 4.7,
        ]);

        ContractorService::create([
            'contractor_id' => $contractor->getKey(),
            'trade' => IssueCategory::Plumbing,
            'base_cost_cents' => 12000,
        ]);

        ContractorAvailability::create([
            'contractor_id' => $contractor->getKey(),
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(12, 0),
        ]);

        return $contractor;
    }

    private function createUnverifiedPlumber(): Contractor
    {
        $contractor = Contractor::create([
            'name' => 'Unverified Plumber',
            'is_verified' => false,
            'rating' => 4.2,
        ]);

        ContractorService::create([
            'contractor_id' => $contractor->getKey(),
            'trade' => IssueCategory::Plumbing,
            'base_cost_cents' => 10000,
        ]);

        ContractorAvailability::create([
            'contractor_id' => $contractor->getKey(),
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(12, 0),
        ]);

        return $contractor;
    }

    private function createTriagedRequest(string $category = 'plumbing'): MaintenanceRequest
    {
        $property = Property::create(['name' => 'Test', 'street' => '1 Main', 'city' => 'Test', 'unit_count' => 1]);
        $unit = Unit::create(['property_id' => $property->getKey(), 'label' => '1']);

        return MaintenanceRequest::create([
            'unit_id' => $unit->getKey(),
            'title' => 'Leaking faucet',
            'description' => 'The kitchen faucet is dripping.',
            'status' => RequestStatus::Triaged,
            'category' => $category,
            'severity' => 'medium',
            'confidence' => 0.95,
        ]);
    }

    public function test_matcher_returns_verified_contractor_with_slot(): void
    {
        $verified = $this->createVerifiedPlumber();
        $unverified = $this->createUnverifiedPlumber();

        $matcher = new ContractorMatcher;
        $result = $matcher->find(IssueCategory::Plumbing);

        $this->assertNotNull($result);
        $this->assertSame($verified->getKey(), $result->contractor->getKey());
    }

    public function test_match_returns_null_when_no_contractors(): void
    {
        $matcher = new ContractorMatcher;
        $result = $matcher->find(IssueCategory::Hvac);

        $this->assertNull($result);
    }

    public function test_match_contractor_action_creates_work_order(): void
    {
        $contractor = $this->createVerifiedPlumber();
        $request = $this->createTriagedRequest();

        $workOrder = app(MatchContractor::class)->execute($request);

        $this->assertNotNull($workOrder);
        $this->assertSame($contractor->getKey(), $workOrder->contractor_id);
        $this->assertSame(WorkOrderStatus::Draft, $workOrder->status);
        $this->assertSame(12000, $workOrder->estimated_cost_cents);
        $this->assertSame(RequestStatus::Dispatching, $request->fresh()->status);
    }

    public function test_match_contractor_returns_null_for_non_triaged_request(): void
    {
        $property = Property::create(['name' => 'Test', 'street' => '1 Main', 'city' => 'Test', 'unit_count' => 1]);
        $unit = Unit::create(['property_id' => $property->getKey(), 'label' => '1']);
        $request = MaintenanceRequest::create([
            'unit_id' => $unit->getKey(),
            'title' => 'test',
            'description' => 'test',
            'status' => RequestStatus::PendingTriage,
        ]);

        $this->createVerifiedPlumber();

        $workOrder = app(MatchContractor::class)->execute($request);

        $this->assertNull($workOrder);
    }

    public function test_approve_work_order_dispatches_to_contractor(): void
    {
        $contractor = $this->createVerifiedPlumber();
        $request = $this->createTriagedRequest();

        $workOrder = app(MatchContractor::class)->execute($request);
        $this->assertNotNull($workOrder);

        $user = User::factory()->create();
        $result = app(ApproveWorkOrder::class)->execute($workOrder, $user->getKey());

        $this->assertSame(WorkOrderStatus::Scheduled, $result->status);
        $this->assertSame($user->getKey(), $result->approved_by);
        $this->assertSame(RequestStatus::Dispatched, $request->fresh()->status);
    }
}
