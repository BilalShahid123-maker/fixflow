<?php

namespace Tests\Unit;

use App\AI\Agents\FakeTriageAgent;
use App\AI\Agents\LlmTriageAgent;
use App\AI\Contracts\TriageAgent;
use App\Jobs\ProcessMaintenanceRequest;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class TriageDriverResolutionTest extends TestCase
{
    public function test_resolves_fake_driver_by_default(): void
    {
        $agent = $this->app->make(TriageAgent::class);

        $this->assertInstanceOf(FakeTriageAgent::class, $agent);
    }

    public function test_resolves_llm_driver_when_configured(): void
    {
        Config::set('fixflow.triage.driver', 'llm');

        $this->assertInstanceOf(LlmTriageAgent::class, $this->app->make(TriageAgent::class));
    }

    public function test_rejects_unknown_driver_with_helpful_error(): void
    {
        Config::set('fixflow.triage.driver', 'skynet');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('skynet');

        $this->app->make(TriageAgent::class);
    }

    public function test_cost_estimation_uses_per_million_token_rates(): void
    {
        Config::set('fixflow.llm.cost_per_million_tokens', ['input' => 3.0, 'output' => 15.0]);

        $job = new ProcessMaintenanceRequest(new MaintenanceRequest);

        $method = new \ReflectionMethod($job, 'estimateCostUsd');

        $cost = $method->invoke($job, ['input_tokens' => 1_000_000, 'output_tokens' => 100_000]);

        $this->assertSame(4.5, $cost);
    }
}
