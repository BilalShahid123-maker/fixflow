<?php

namespace Tests\Feature;

use App\Mcp\FixFlowMcpServer;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private function server(): FixFlowMcpServer
    {
        return new FixFlowMcpServer;
    }

    private function rpc(mixed $id, string $method, array $params = []): string
    {
        return json_encode(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params]);
    }

    public function test_initialize_returns_server_info(): void
    {
        $reply = $this->server()->handle($this->rpc(1, 'initialize', [
            'protocolVersion' => '2025-03-26',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1'],
        ]));

        $this->assertSame('fixflow', $reply['result']['serverInfo']['name']);
        $this->assertSame('2025-03-26', $reply['result']['protocolVersion']);
    }

    public function test_tools_list_exposes_fixflow_tools(): void
    {
        $reply = $this->server()->handle($this->rpc(2, 'tools/list'));

        $names = array_column($reply['result']['tools'], 'name');

        $this->assertContains('submit_maintenance_request', $names);
        $this->assertContains('get_maintenance_request', $names);
        $this->assertContains('list_properties', $names);
    }

    public function test_submit_tool_queues_request_into_pipeline(): void
    {
        $property = Property::create(['name' => 'Maple', 'street' => '1 Maple', 'city' => 'Springfield']);
        $unit = Unit::create(['property_id' => $property->getKey(), 'label' => '10']);

        $reply = $this->server()->handle($this->rpc(3, 'tools/call', [
            'name' => 'submit_maintenance_request',
            'arguments' => [
                'property_id' => $property->getKey(),
                'unit_id' => $unit->getKey(),
                'tenant_name' => 'Cara',
                'tenant_email' => 'cara@example.test',
                'title' => 'Heater making a loud bang',
                'description' => 'The wall heater bangs loudly for minutes after turning on.',
            ],
        ]));

        $text = $reply['result']['content'][0]['text'];
        $payload = json_decode($text, true);

        $this->assertTrue($payload['ok']);
        $this->assertNotNull($payload['reference']);

        $this->assertDatabaseHas('maintenance_requests', ['reference' => $payload['reference']]);
    }

    public function test_submit_tool_validates_input(): void
    {
        $reply = $this->server()->handle($this->rpc(4, 'tools/call', [
            'name' => 'submit_maintenance_request',
            'arguments' => ['title' => 'x'],
        ]));

        $this->assertArrayHasKey('error', $reply);
        $this->assertSame(-32603, $reply['error']['code']);
    }

    public function test_unknown_tool_returns_error(): void
    {
        $reply = $this->server()->handle($this->rpc(5, 'tools/call', [
            'name' => 'nope',
            'arguments' => [],
        ]));

        $this->assertArrayHasKey('error', $reply);
        $this->assertSame(-32602, $reply['error']['code']);
    }

    public function test_notification_is_ignored(): void
    {
        $this->assertNull($this->server()->handle(
            json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])
        ));
    }
}
