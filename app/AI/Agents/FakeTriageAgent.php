<?php

namespace App\AI\Agents;

use App\AI\Contracts\TriageAgent;
use App\AI\Dto\TriageResult;
use App\Enums\IssueCategory;
use App\Enums\Severity;
use App\Models\MaintenanceRequest;

class FakeTriageAgent implements TriageAgent
{
    private const EMERGENCY_KEYWORDS = ['smoke', 'fire', 'spark', 'sparking', 'flood', 'gas leak', 'burst'];

    private const CATEGORY_KEYWORDS = [
        'plumbing' => ['leak', 'leaking', 'pipe', 'sink', 'toilet', 'clog', 'clogged', 'drain', 'faucet', 'tap', 'shower', 'water'],
        'electrical' => ['outlet', 'electric', 'electrical', 'breaker', 'wiring', 'socket', 'light fixture', 'power'],
        'hvac' => ['ac ', 'a/c', 'air condition', 'heating', 'heater', 'furnace', 'thermostat', 'hvac', 'radiator'],
        'appliance' => ['fridge', 'refrigerator', 'oven', 'stove', 'dishwasher', 'washer', 'dryer', 'microwave', 'garbage disposal'],
        'structural' => ['wall', 'ceiling', 'roof', 'foundation', 'crack', 'flooring', 'window frame', 'door frame'],
    ];

    private const LOW_SEVERITY_KEYWORDS = ['cosmetic', 'squeak', 'paint', 'scratch', 'slow drip when off'];

    public function triage(MaintenanceRequest $request): TriageResult
    {
        $text = mb_strtolower($request->title.' '.$request->description);

        $isEmergency = $this->containsAny($text, self::EMERGENCY_KEYWORDS);
        [$category, $categoryHits] = $this->detectCategory($text);
        $severity = $this->detectSeverity($text, $category, $isEmergency);

        $confidence = match (true) {
            $isEmergency => 0.97,
            $categoryHits >= 2 => 0.94,
            $categoryHits === 1 => 0.88,
            default => 0.55,
        };

        return new TriageResult(
            category: $category,
            severity: $severity,
            confidence: $confidence,
            isEmergency: $isEmergency,
            reasoning: sprintf(
                '%s keywords matched for category "%s"%s.',
                $categoryHits,
                $category->value,
                $isEmergency ? '; emergency keyword present' : '',
            ),
        );
    }

    private function detectCategory(string $text): array
    {
        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
            $hits = $this->countMatches($text, $keywords);
            if ($hits > 0) {
                return [IssueCategory::from($category), $hits];
            }
        }

        return [IssueCategory::General, 0];
    }

    private function detectSeverity(string $text, IssueCategory $category, bool $isEmergency): Severity
    {
        if ($isEmergency) {
            return Severity::Critical;
        }

        if ($this->containsAny($text, self::LOW_SEVERITY_KEYWORDS)) {
            return Severity::Low;
        }

        return match ($category) {
            IssueCategory::Electrical => Severity::High,
            IssueCategory::Plumbing => str_contains($text, 'leak') || str_contains($text, 'clog') ? Severity::High : Severity::Medium,
            IssueCategory::Structural => Severity::High,
            default => Severity::Medium,
        };
    }

    private function countMatches(string $text, array $keywords): int
    {
        return collect($keywords)->filter(fn (string $keyword) => str_contains($text, $keyword))->count();
    }

    private function containsAny(string $text, array $keywords): bool
    {
        return collect($keywords)->contains(fn (string $keyword) => str_contains($text, $keyword));
    }
}
