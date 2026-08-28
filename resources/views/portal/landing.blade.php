<x-portal-layout title="Property maintenance portal">
    <div class="card center">
        <h1>Report a maintenance issue</h1>
        <p class="lead">Tell us what's wrong and FixFlow's AI triage engine will route it to the right contractor — fast.</p>
        <div class="steps">
            <div class="step"><span class="dot">1.</span> You submit the issue and your unit details</div>
            <div class="step"><span class="dot">2.</span> AI triages it by category, severity &amp; urgency</div>
            <div class="step"><span class="dot">3.</span> A manager approves the best contractor</div>
            <div class="step"><span class="dot">4.</span> Track progress with your reference number</div>
        </div>
        <a class="btn" href="{{ route('portal.create') }}">Report an issue</a>
        <p class="mt muted" style="font-size:13px">Emergency? Call the maintenance line immediately.</p>
    </div>
</x-portal-layout>
