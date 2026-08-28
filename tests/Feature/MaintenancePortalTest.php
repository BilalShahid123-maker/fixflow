<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenancePortalTest extends TestCase
{
    use RefreshDatabase;

    private function seedProperty(): array
    {
        $property = Property::create(['name' => 'Maple Court', 'street' => '12 Maple', 'city' => 'Springfield', 'unit_count' => 1]);
        $unit = Unit::create(['property_id' => $property->getKey(), 'label' => '101']);

        return [$property, $unit];
    }

    public function test_landing_page_renders(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('FixFlow');
    }

    public function test_submit_form_renders(): void
    {
        $this->get(route('portal.create'))->assertOk()->assertSee('Report a maintenance issue');
    }

    public function test_tenant_can_submit_request_into_triage_pipeline(): void
    {
        [$property, $unit] = $this->seedProperty();

        $response = $this->post(route('portal.store'), [
            'property_id' => $property->getKey(),
            'unit_id' => $unit->getKey(),
            'tenant_name' => 'Dana Whitfield',
            'tenant_email' => 'dana@example.test',
            'title' => 'Water leaking under the kitchen sink',
            'description' => 'There is water pooling under the sink and the cabinet is soaked.',
        ]);

        $request = MaintenanceRequest::query()->first();

        $this->assertNotNull($request);
        $this->assertNotNull($request->reference);
        $this->assertSame('dana@example.test', $request->tenant?->email);

        // QUEUE_CONNECTION=sync means triage ran during dispatch.
        $this->assertSame(RequestStatus::Triaged, $request->status);
        $this->assertSame('plumbing', $request->category->value);

        $response->assertRedirect(route('portal.status', ['reference' => $request->reference]));
    }

    public function test_submit_validation_requires_description(): void
    {
        [$property, $unit] = $this->seedProperty();

        $this->post(route('portal.store'), [
            'property_id' => $property->getKey(),
            'unit_id' => $unit->getKey(),
            'tenant_name' => 'Dana',
            'tenant_email' => 'dana@example.test',
            'title' => 'Leak',
            'description' => 'Too short',
        ])->assertSessionHasErrors(['description']);
    }

    public function test_public_status_page_shows_reference_and_pipeline_state(): void
    {
        [$property, $unit] = $this->seedProperty();

        $this->post(route('portal.store'), [
            'property_id' => $property->getKey(),
            'unit_id' => $unit->getKey(),
            'tenant_name' => 'Dana Whitfield',
            'tenant_email' => 'dana@example.test',
            'title' => 'Water leaking under the kitchen sink',
            'description' => 'There is water pooling under the sink and the cabinet is soaked.',
        ]);

        $request = MaintenanceRequest::query()->first();

        $this->get(route('portal.status', ['reference' => $request->reference]))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('Triaged');
    }
}
