<x-portal-layout title="Report an issue">
    <div class="card">
        <h1>Report a maintenance issue</h1>
        <p class="lead">Fill this in and our AI triage will pick the right category and urgency.</p>

        @if ($errors->any())
            <div class="alert" style="background:#fef2f2;border-color:#fecaca;color:#991b1b">
                <ul style="margin:0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('portal.store') }}">
            @csrf
            <label for="property_id">Property</label>
            <select id="property_id" name="property_id" required>
                <option value="">Select your property…</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->getKey() }}" @selected(old('property_id') == $property->getKey())>
                        {{ $property->name }} — {{ $property->city }}
                    </option>
                @endforeach
            </select>

            <label for="unit_id">Unit</label>
            <select id="unit_id" name="unit_id" required>
                <option value="">Select your unit…</option>
                @foreach ($properties as $property)
                    @foreach ($property->units as $unit)
                        <option value="{{ $unit->getKey() }}" data-property="{{ $property->getKey() }}" @selected(old('unit_id') == $unit->getKey())>
                            {{ $property->name }} — Unit {{ $unit->label }}
                        </option>
                    @endforeach
                @endforeach
            </select>

            <div class="grid">
                <div>
                    <label for="tenant_name">Your name</label>
                    <input id="tenant_name" name="tenant_name" value="{{ old('tenant_name') }}" required>
                </div>
                <div>
                    <label for="tenant_email">Your email</label>
                    <input id="tenant_email" type="email" name="tenant_email" value="{{ old('tenant_email') }}" required>
                </div>
            </div>

            <label for="title">Short summary</label>
            <input id="title" name="title" value="{{ old('title') }}" required
                   placeholder="e.g. Water leaking under kitchen sink">

            <label for="description">What's happening?</label>
            <textarea id="description" name="description" required
                      placeholder="Describe the problem. How long has it been happening? Is it urgent?">{{ old('description') }}</textarea>

            <button type="submit" class="btn mt">Submit request</button>
        </form>
    </div>
</x-portal-layout>
