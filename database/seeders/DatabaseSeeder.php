<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Jobs\ProcessMaintenanceRequest;
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
    }
}
