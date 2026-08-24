<?php

namespace App\Providers;

use App\AI\Agents\FakeTriageAgent;
use App\AI\Agents\LlmTriageAgent;
use App\AI\Contracts\TriageAgent;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TriageAgent::class, function () {
            return match (config('fixflow.triage.driver')) {
                'fake' => new FakeTriageAgent,
                'llm' => new LlmTriageAgent,
                default => throw new InvalidArgumentException(
                    'Unsupported triage driver ['.config('fixflow.triage.driver').'].',
                ),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
