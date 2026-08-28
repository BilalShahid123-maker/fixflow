<?php

namespace App\Http\Controllers;

use App\Actions\SubmitMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use Illuminate\Http\Request;

class MaintenancePortalController extends Controller
{
    public function landing()
    {
        return view('portal.landing');
    }

    public function create()
    {
        $properties = Property::query()->with('units')->orderBy('name')->get();

        return view('portal.submit', ['properties' => $properties]);
    }

    public function store(Request $request, SubmitMaintenanceRequest $submitter)
    {
        $validated = $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'tenant_name' => ['required', 'string', 'max:120'],
            'tenant_email' => ['required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:15', 'max:5000'],
        ]);

        $maintenanceRequest = $submitter->execute($validated);

        return redirect()->route('portal.status', ['reference' => $maintenanceRequest->reference])
            ->with('status', 'Request submitted. Your reference number is below.');
    }

    public function status(string $reference)
    {
        $maintenanceRequest = MaintenanceRequest::query()
            ->where('reference', $reference)
            ->with(['unit.property', 'tenant', 'workOrders.contractor'])
            ->firstOrFail();

        return view('portal.status', ['request' => $maintenanceRequest]);
    }
}
