<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\RequestStatus;
use App\Jobs\ProcessMaintenanceRequest;
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
            ['name' => 'Property Manager', 'password' => 'password'],
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

        $this->seedKnowledge();
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
