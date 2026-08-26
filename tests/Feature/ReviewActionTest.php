<?php

namespace Tests\Feature;

use App\Actions\ApproveReview;
use App\Actions\RejectReview;
use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewActionTest extends TestCase
{
    use RefreshDatabase;

    private function createAwaitingRequest(): MaintenanceRequest
    {
        $property = Property::create([
            'name' => 'Maple Court', 'street' => '1 Main St', 'city' => 'Springfield', 'unit_count' => 1,
        ]);
        $unit = Unit::create(['property_id' => $property->getKey(), 'label' => '101']);

        return MaintenanceRequest::create([
            'unit_id' => $unit->getKey(),
            'title' => 'Odd noise',
            'description' => 'Not sure what this is.',
            'status' => RequestStatus::AwaitingApproval,
            'confidence' => 0.55,
        ]);
    }

    public function test_approve_moves_request_to_triaged_and_audits_the_decision(): void
    {
        $request = $this->createAwaitingRequest();
        $user = User::factory()->create();

        $result = app(ApproveReview::class)->execute($request, $user->id);

        $this->assertSame(RequestStatus::Triaged, $result->status);
        $this->assertSame(1, AuditLog::where('event', 'review.approved')->count());
        $this->assertSame($user->id, AuditLog::where('event', 'review.approved')->first()->actor_id);
    }

    public function test_reject_moves_request_to_rejected_and_stores_note(): void
    {
        $request = $this->createAwaitingRequest();
        $user = User::factory()->create();

        $result = app(RejectReview::class)->execute($request, $user->id, 'Duplicate of request #12');

        $this->assertSame(RequestStatus::Rejected, $result->status);

        $log = AuditLog::where('event', 'review.rejected')->first();
        $this->assertNotNull($log);
        $this->assertSame('Duplicate of request #12', $log->properties['note']);
    }
}
