<?php

namespace Database\Seeders;

use App\Enums\IssueCategory;
use App\Enums\RequestStatus;
use App\Jobs\ProcessMaintenanceRequest;
use App\Models\Contractor;
use App\Models\ContractorAvailability;
use App\Models\ContractorService;
use App\Models\KnowledgeDocument;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@fixflow.test'],
            ['name' => 'Property Manager', 'password' => 'password', 'is_admin' => true],
        );

        $property = Property::create([
            'name' => 'Maple Court',
            'street' => '12 Maple Street',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62704',
            'unit_count' => 2,
        ]);

        $unit302 = Unit::create(['property_id' => $property->getKey(), 'label' => '302', 'bedrooms' => 2, 'floor' => 3]);
        $unit118 = Unit::create(['property_id' => $property->getKey(), 'label' => '118', 'bedrooms' => 1, 'floor' => 1]);

        Tenant::create(['unit_id' => $unit302->getKey(), 'name' => 'Dana Whitfield', 'email' => 'dana@example.test', 'is_active' => true]);
        Tenant::create(['unit_id' => $unit118->getKey(), 'name' => 'Marcus Lee', 'email' => 'marcus@example.test', 'is_active' => true]);

        $requests = [
            [
                'unit_id' => $unit302->getKey(),
                'title' => 'Water leaking under kitchen sink',
                'description' => 'There is a leak coming from the pipe under the kitchen sink. Water is pooling inside the cabinet and I cannot stop it.',
            ],
            [
                'unit_id' => $unit118->getKey(),
                'title' => 'Smoke coming from outlet',
                'description' => 'There is smoke coming from the electrical socket next to the TV and it smells burnt.',
            ],
            [
                'unit_id' => $unit118->getKey(),
                'title' => 'Strange smell in hallway',
                'description' => 'I noticed an odd smell near the hallway closet but honestly I am not sure what it is or where exactly it comes from.',
            ],
        ];

        foreach ($requests as $data) {
            $request = MaintenanceRequest::create($data + ['status' => RequestStatus::PendingTriage]);

            ProcessMaintenanceRequest::dispatchSync($request);
        }

        $this->command?->info("Seeded admin [{$admin->email}] with password 'password' and ".count($requests).' demo requests.');

        $this->seedContractors();
        $this->seedKnowledge();
        $this->call(EvalDatasetSeeder::class);
    }

    private function seedContractors(): void
    {
        $plumber = Contractor::create(['name' => 'Raj Patel', 'company' => 'Patel Plumbing Co.', 'phone' => '555-0101', 'email' => 'raj@patelplumbing.test', 'is_verified' => true, 'rating' => 4.8]);
        ContractorService::create(['contractor_id' => $plumber->getKey(), 'trade' => IssueCategory::Plumbing, 'base_cost_cents' => 15000, 'hourly_rate_cents' => 8500]);
        ContractorAvailability::create(['contractor_id' => $plumber->getKey(), 'starts_at' => now()->addDays(1)->setTime(9, 0), 'ends_at' => now()->addDays(1)->setTime(12, 0)]);
        ContractorAvailability::create(['contractor_id' => $plumber->getKey(), 'starts_at' => now()->addDays(2)->setTime(13, 0), 'ends_at' => now()->addDays(2)->setTime(17, 0)]);

        $electrician = Contractor::create(['name' => 'Sara Chen', 'company' => 'Chen Electric', 'phone' => '555-0202', 'email' => 'sara@chenelectric.test', 'is_verified' => true, 'rating' => 4.9]);
        ContractorService::create(['contractor_id' => $electrician->getKey(), 'trade' => IssueCategory::Electrical, 'base_cost_cents' => 20000, 'hourly_rate_cents' => 9500]);
        ContractorAvailability::create(['contractor_id' => $electrician->getKey(), 'starts_at' => now()->addDays(1)->setTime(10, 0), 'ends_at' => now()->addDays(1)->setTime(14, 0)]);

        $hvac = Contractor::create(['name' => 'Tom Rivera', 'company' => 'Rivera HVAC Services', 'phone' => '555-0303', 'email' => 'tom@riverahvac.test', 'is_verified' => true, 'rating' => 4.5]);
        ContractorService::create(['contractor_id' => $hvac->getKey(), 'trade' => IssueCategory::Hvac, 'base_cost_cents' => 25000, 'hourly_rate_cents' => 11000]);
        ContractorAvailability::create(['contractor_id' => $hvac->getKey(), 'starts_at' => now()->addDays(3)->setTime(8, 0), 'ends_at' => now()->addDays(3)->setTime(12, 0)]);

        $this->command?->info('Seeded 3 contractors (plumbing, electrical, HVAC) with availability.');
    }

    private function seedKnowledge(): void
    {
        $docs = [
            [
                'title' => 'Plumbing Maintenance Guide',
                'source_ref' => 'guide-plumbing-001',
                'content' => 'Water leaking under a kitchen sink is usually caused by a loose P-trap compression nut or a failed supply line gasket. Shut off the local angle stop valve, place a bucket under the pipe, and tighten the compression nut by hand. If the leak persists, replace the supply line. A running toilet typically indicates a worn flapper valve or a float that needs adjustment. Leaking faucets are almost always caused by a failed cartridge or O-ring — turn off the local shutoff valve before beginning repairs. Burst pipes in winter require immediate shutoff at the main valve and a call to the emergency maintenance line.',
            ],
            [
                'title' => 'Electrical Safety Protocol',
                'source_ref' => 'guide-electrical-001',
                'content' => 'Smoke or burning smell from an electrical outlet is a serious fire hazard. Evacuate the area immediately and do not use the outlet. Call the emergency maintenance line and 911 if there are visible flames. Do not attempt to extinguish an electrical fire with water. sparking outlets indicate a short circuit that requires a licensed electrician. Tripped breakers that reset immediately usually indicate a faulty device rather than a wiring issue.',
            ],
            [
                'title' => 'HVAC Troubleshooting',
                'source_ref' => 'guide-hvac-001',
                'content' => 'HVAC filters should be replaced every 90 days in occupied units. A unit that blows warm air on the cooling setting is likely low on refrigerant and requires a licensed technician. Thermostats that do not respond to input should have their batteries replaced first. No heat during winter months is an emergency priority that must be dispatched same-day. Strange noises from the air handler — rattling, squealing, or grinding — indicate mechanical wear and should be scheduled as medium priority.',
            ],
        ];

        foreach ($docs as $data) {
            $doc = KnowledgeDocument::create($data + ['is_published' => true]);
            $chunks = $doc->ingest();
            $this->command?->info("Knowledge [{$doc->title}]: {$chunks} chunks embedded.");
        }
    }
}
