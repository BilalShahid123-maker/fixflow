<x-portal-layout title="Request status">
    <div class="card">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        <h1>Request status</h1>
        <p class="muted" style="margin-bottom:8px">Your reference number:</p>
        <span class="ref">{{ $request->reference }}</span>

        <p class="mt" style="font-size:15px">
            <strong>{{ $request->title }}</strong>
        </p>

        <div class="card" style="box-shadow:none;background:#f9fafb;border-color:var(--line)">
            <table style="width:100%;border-collapse:collapse;font-size:14px">
                <tr>
                    <td style="padding:8px 0;color:var(--muted);width:160px">Status</td>
                    <td>
                        @php
                            $statusColor = match ($request->status->value) {
                                'pending_triage' => '#f59e0b',
                                'awaiting_approval' => '#f59e0b',
                                'triaged', 'dispatching' => '#3b82f6',
                                'dispatched', 'in_progress' => '#6366f1',
                                'completed' => '#10b981',
                                'rejected' => '#ef4444',
                                default => '#6b7280',
                            };
                        @endphp
                        <span class="pill" style="background:{{ $statusColor }}22;color:{{ $statusColor }}">
                            {{ $request->status->label() }}
                        </span>
                    </td>
                </tr>
                @if ($request->category)
                <tr>
                    <td style="padding:8px 0;color:var(--muted)">Category</td>
                    <td style="text-transform:capitalize">{{ $request->category->value }}</td>
                </tr>
                @endif
                @if ($request->severity)
                <tr>
                    <td style="padding:8px 0;color:var(--muted)">Priority</td>
                    <td style="text-transform:capitalize">
                        @if ($request->emergency)<span class="pill" style="background:#fee2e2;color:#b91c1c">Emergency</span> @endif
                        {{ $request->severity->value }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding:8px 0;color:var(--muted)">Submitted</td>
                    <td>{{ $request->created_at->format('M j, Y g:ia') }}</td>
                </tr>
                @if ($latest = $request->latestAiRun()?->output['reasoning'] ?? null)
                <tr>
                    <td style="padding:8px 0;color:var(--muted);vertical-align:top">Assessment</td>
                    <td>{{ $latest }}</td>
                </tr>
                @endif
                @if ($workOrder = $request->workOrders->first())
                <tr>
                    <td style="padding:8px 0;color:var(--muted)">Contractor</td>
                    <td>{{ $workOrder->contractor?->name ?? 'Pending' }}</td>
                </tr>
                @if ($workOrder->scheduled_for)
                <tr>
                    <td style="padding:8px 0;color:var(--muted)">Scheduled</td>
                    <td>{{ $workOrder->scheduled_for->format('M j, Y g:ia') }}</td>
                </tr>
                @endif
                @endif
            </table>
        </div>

        <p style="margin-top:20px">
            <a href="{{ route('portal.create') }}" class="btn">Report another issue</a>
        </p>
    </div>
</x-portal-layout>
