<?php

namespace App\Providers;

use App\AI\Agents\FakeTriageAgent;
use App\AI\Agents\LlmTriageAgent;
use App\AI\Contracts\TriageAgent;
use App\AI\RAG\EmbeddingService;
use App\AI\RAG\FakeEmbeddingService;
use App\AI\RAG\LlmEmbeddingService;
use App\AI\RAG\VectorSearch;
use App\Models\MaintenanceRequest;
use App\Policies\MaintenanceRequestPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmbeddingService::class, fn () => match (config('fixflow.rag.embedding.driver')) {
            'llm' => new LlmEmbeddingService,
            default => new FakeEmbeddingService,
        });

        $this->app->singleton(VectorSearch::class);

        $this->app->bind(TriageAgent::class, function () {
            return match (config('fixflow.triage.driver')) {
                'fake' => new FakeTriageAgent,
                'llm' => new LlmTriageAgent(
                    embeddingService: app(EmbeddingService::class),
                    vectorSearch: app(VectorSearch::class),
                ),
                default => throw new InvalidArgumentException(
                    'Unsupported triage driver ['.config('fixflow.triage.driver').'].',
                ),
            };
        });
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        $this->app->make(Gate::class)->policy(
            MaintenanceRequest::class,
            MaintenanceRequestPolicy::class,
        );
    }
}
