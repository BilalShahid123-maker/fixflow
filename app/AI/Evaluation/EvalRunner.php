<?php

namespace App\AI\Evaluation;

use App\AI\Contracts\TriageAgent;
use App\Enums\IssueCategory;
use App\Enums\RequestStatus;
use App\Enums\Severity;
use App\Models\AiRun;
use App\Models\EvalCase;
use App\Models\EvalRun;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Collection;

class EvalRunner
{
    public function __construct(
        private TriageAgent $agent,
    ) {}

    /**
     * Run the triage agent over all eval cases and record per-case results.
     *
     * @return Collection<int, EvalRun>
     */
    public function runAll(?string $split = 'eval'): Collection
    {
        return EvalCase::query()
            ->when($split, fn ($q) => $q->where('split', $split))
            ->get()
            ->map(fn (EvalCase $case) => $this->runOne($case));
    }

    public function runOne(EvalCase $case): EvalRun
    {
        $request = $this->makeStandInRequest($case);

        $result = $this->agent->triage($request);

        $aiRun = AiRun::create([
            'maintenance_request_id' => $request->getKey(),
            'agent' => $this->agent::class,
            'provider' => config('fixflow.triage.driver'),
            'status' => 'succeeded',
            'input_payload' => [
                'title' => $case->title,
                'description' => $case->description,
            ],
            'output' => $result->toArray(),
            'input_tokens' => $result->meta['input_tokens'] ?? 0,
            'output_tokens' => $result->meta['output_tokens'] ?? 0,
        ]);

        $categoryExpected = IssueCategory::tryFrom($case->category);
        $severityExpected = Severity::tryFrom($case->severity);

        return EvalRun::create([
            'eval_case_id' => $case->getKey(),
            'ai_run_id' => $aiRun->getKey(),
            'category_expected' => $case->category,
            'category_predicted' => $result->category->value,
            'severity_expected' => $case->severity,
            'severity_predicted' => $result->severity->value,
            'category_correct' => $categoryExpected === $result->category,
            'severity_correct' => $severityExpected === $result->severity,
            'critical_expected' => (bool) $case->emergency,
            'critical_correct' => $case->emergency
                ? $result->isEmergency
                : true,
            'confidence' => $result->confidence,
        ]);
    }

    private function makeStandInRequest(EvalCase $case): MaintenanceRequest
    {
        static $propertyId = null;
        static $unitId = null;

        if ($propertyId === null) {
            $property = Property::create(['name' => 'Eval', 'street' => '1 Eval St', 'city' => 'Test', 'unit_count' => 1]);
            $propertyId = $property->getKey();
            $unit = Unit::create(['property_id' => $propertyId, 'label' => 'EVAL-1']);
            $unitId = $unit->getKey();
        }

        return MaintenanceRequest::create([
            'unit_id' => $unitId,
            'title' => $case->title,
            'description' => $case->description,
            'status' => RequestStatus::PendingTriage,
        ]);
    }
}
