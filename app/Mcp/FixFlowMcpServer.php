<?php

namespace App\Mcp;

use App\Actions\SubmitMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Validator;

/**
 * Minimal stdio MCP server exposing FixFlow as tools to AI assistants.
 *
 * Speaks the Model Context Protocol (JSON-RPC 2.0 over stdio, newline-delimited).
 * Booted via bin/fixflow-mcp; only happy-path framework logs go to stderr.
 */
class FixFlowMcpServer
{
    private array $tools;

    public function __construct()
    {
        $this->tools = [
            'submit_maintenance_request' => [
                'description' => 'Create a tenant maintenance request from a property unit. The request enters the AI triage pipeline (category, severity, emergency, confidence) asynchronously. Returns the reference number and initial status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'description' => 'ID of the property the unit belongs to'],
                        'unit_id' => ['type' => 'integer', 'description' => 'ID of the unit the tenant reports from'],
                        'tenant_name' => ['type' => 'string', 'description' => 'Tenant name'],
                        'tenant_email' => ['type' => 'string', 'description' => 'Tenant contact email'],
                        'title' => ['type' => 'string', 'description' => 'Short summary, e.g. "Water leaking under the kitchen sink"'],
                        'description' => ['type' => 'string', 'description' => 'Free-form description of the problem (min 15 chars)'],
                    ],
                    'required' => ['property_id', 'unit_id', 'tenant_name', 'tenant_email', 'title', 'description'],
                ],
                'handler' => fn (array $args) => $this->submitMaintenanceRequest($args),
            ],
            'list_maintenance_requests' => [
                'description' => 'List maintenance requests, optionally filtered by status. Statuses: pending_triage, triaged, awaiting_approval, dispatching, dispatched, in_progress, completed, rejected.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['pending_triage', 'triaged', 'awaiting_approval', 'dispatching', 'dispatched', 'in_progress', 'completed', 'rejected'], 'description' => 'Filter by status'],
                        'limit' => ['type' => 'integer', 'description' => 'Max rows (default 25)'],
                    ],
                    'required' => [],
                ],
                'handler' => fn (array $args) => $this->listMaintenanceRequests($args),
            ],
            'get_maintenance_request' => [
                'description' => 'Full detail on one maintenance request, including triage assessment and any assigned work order / contractor.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reference' => ['type' => 'string', 'description' => 'Public reference number (e.g. ABC12345)'],
                    ],
                    'required' => ['reference'],
                ],
                'handler' => fn (array $args) => $this->getMaintenanceRequest($args),
            ],
            'list_work_orders' => [
                'description' => 'List dispatched work orders with contractor and cost info, optionally filtered by status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'description' => 'WorkOrderStatus value: draft, scheduled, in_progress, completed, cancelled'],
                        'limit' => ['type' => 'integer', 'description' => 'Max rows (default 25)'],
                    ],
                    'required' => [],
                ],
                'handler' => fn (array $args) => $this->listWorkOrders($args),
            ],
            'list_properties' => [
                'description' => 'List properties and their units so you can submit maintenance requests against real IDs.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
                'handler' => fn (array $args) => $this->listProperties(),
            ],
        ];
    }

    public function run(): never
    {
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $reply = $this->handle($line);

            if ($reply !== null) {
                fwrite(STDOUT, json_encode($reply, JSON_UNESCAPED_SLASHES)."\n");
                fflush(STDOUT);
            }
        }

        exit(0);
    }

    /**
     * Process a single newline-delimited JSON-RPC message.
     *
     * @return array{jsonrpc: string, id: mixed, result?: mixed, error?: array{code:int,message:string}}|null
     */
    public function handle(string $line): ?array
    {
        $json = json_decode($line, true);

        if (! is_array($json) || ! isset($json['method'])) {
            return $this->reply(null, ['code' => -32600, 'message' => 'Invalid Request'], $json['id'] ?? null);
        }

        if ($json['method'] === 'initialize') {
            return $this->reply([
                'protocolVersion' => $json['params']['protocolVersion'] ?? '2025-03-26',
                'capabilities' => ['tools' => new \stdClass],
                'serverInfo' => ['name' => 'fixflow', 'version' => '1.0.0'],
            ], null, $json['id']);
        }

        if ($json['method'] === 'notifications/initialized' || str_starts_with($json['method'], 'notifications/')) {
            return null;
        }

        if ($json['method'] === 'ping') {
            return $this->reply([], null, $json['id']);
        }

        if ($json['method'] === 'tools/list') {
            return $this->reply([
                'tools' => array_map(
                    fn (string $name, array $def) => [
                        'name' => $name,
                        'description' => $def['description'],
                        'inputSchema' => $def['parameters'],
                    ],
                    array_keys($this->tools),
                    array_values($this->tools),
                ),
            ], null, $json['id']);
        }

        if ($json['method'] === 'tools/call') {
            return $this->callTool($json['params'] ?? [], $json['id']);
        }

        return $this->reply(null, ['code' => -32601, 'message' => "Method not found: {$json['method']}"], $json['id']);
    }

    private function callTool(array $params, mixed $id): ?array
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! is_string($name) || ! isset($this->tools[$name])) {
            return $this->reply(null, ['code' => -32602, 'message' => "Unknown tool: {$name}"], $id);
        }

        try {
            $result = ($this->tools[$name]['handler'])($arguments);

            return $this->reply([
                'content' => [
                    ['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
                ],
            ], null, $id);
        } catch (\Throwable $e) {
            return $this->reply(null, ['code' => -32603, 'message' => "{$e->getMessage()} ({$e->getFile()}:{$e->getLine()})"], $id);
        }
    }

    private function submitMaintenanceRequest(array $args): array
    {
        $validator = Validator::make($args, [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'tenant_name' => ['required', 'string', 'max:120'],
            'tenant_email' => ['required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:15', 'max:5000'],
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        $request = app(SubmitMaintenanceRequest::class)->execute($validator->validated());
        $request->load('unit');

        return [
            'ok' => true,
            'reference' => $request->reference,
            'id' => $request->getKey(),
            'status' => $request->status->value,
            'title' => $request->title,
            'unit' => $request->unit->label,
            'message' => 'Request submitted and queued for AI triage.',
        ];
    }

    private function listMaintenanceRequests(array $args): array
    {
        $query = MaintenanceRequest::query()
            ->with(['unit.property', 'workOrders.contractor'])
            ->latest('id')
            ->limit((int) ($args['limit'] ?? 25));

        if (! empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        return $query->get()->map(fn (MaintenanceRequest $m) => [
            'reference' => $m->reference,
            'title' => $m->title,
            'status' => $m->status->value,
            'category' => $m->category?->value,
            'severity' => $m->severity?->value,
            'emergency' => $m->emergency,
            'confidence' => $m->confidence,
            'unit' => $m->unit?->label,
            'property' => $m->unit?->property?->name,
            'work_order' => $m->workOrders->first()?->status->value,
        ])->all();
    }

    private function getMaintenanceRequest(array $args): array
    {
        $maintenanceRequest = MaintenanceRequest::query()
            ->where('reference', $args['reference'])
            ->orWhere('id', $args['reference'])
            ->with(['unit.property', 'tenant', 'workOrders.contractor'])
            ->firstOrFail();

        $aiRun = $maintenanceRequest->latestAiRun();

        return [
            'reference' => $maintenanceRequest->reference,
            'title' => $maintenanceRequest->title,
            'description' => $maintenanceRequest->description,
            'status' => $maintenanceRequest->status->value,
            'category' => $maintenanceRequest->category?->value,
            'severity' => $maintenanceRequest->severity?->value,
            'emergency' => $maintenanceRequest->emergency,
            'confidence' => $maintenanceRequest->confidence,
            'unit' => $maintenanceRequest->unit?->label,
            'property' => $maintenanceRequest->unit?->property?->name,
            'tenant' => $maintenanceRequest->tenant?->name,
            'ai_assessment' => $aiRun?->output['reasoning'] ?? null,
            'ai_model' => $aiRun?->model,
            'ai_latency_ms' => $aiRun?->latency_ms,
            'ai_cost_usd' => $aiRun?->cost_usd,
            'work_order' => $maintenanceRequest->workOrders->first() ? [
                'status' => $maintenanceRequest->workOrders->first()->status->value,
                'contractor' => $maintenanceRequest->workOrders->first()->contractor?->name,
                'estimated_cost_usd' => ($c = $maintenanceRequest->workOrders->first()->estimated_cost_cents) !== null
                    ? $c / 100
                    : null,
                'scheduled_for' => $maintenanceRequest->workOrders->first()->scheduled_for?->toIso8601String(),
            ] : null,
        ];
    }

    private function listWorkOrders(array $args): array
    {
        $query = WorkOrder::query()
            ->with(['request', 'contractor'])
            ->latest('id')
            ->limit((int) ($args['limit'] ?? 25));

        if (! empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        return $query->get()->map(fn (WorkOrder $w) => [
            'id' => $w->getKey(),
            'status' => $w->status->value,
            'contractor' => $w->contractor?->name,
            'estimated_cost_usd' => ($c = $w->estimated_cost_cents) !== null ? $c / 100 : null,
            'scheduled_for' => $w->scheduled_for?->toIso8601String(),
            'request_reference' => $w->request?->reference,
        ])->all();
    }

    private function listProperties(): array
    {
        return Property::query()->with('units')->orderBy('name')->get()->map(fn (Property $p) => [
            'id' => $p->getKey(),
            'name' => $p->name,
            'city' => $p->city,
            'units' => $p->units->map(fn (Unit $u) => [
                'id' => $u->getKey(),
                'label' => $u->label,
                'bedrooms' => $u->bedrooms,
            ]),
        ])->all();
    }

    private function reply(mixed $result, ?array $error, mixed $id): array
    {
        return array_filter([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
            'error' => $error,
        ], fn ($v) => ! is_null($v));
    }
}
