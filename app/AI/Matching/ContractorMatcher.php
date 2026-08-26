<?php

namespace App\AI\Matching;

use App\Enums\IssueCategory;
use App\Models\Contractor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class ContractorMatcher
{
    /**
     * Find the best contractor for a given trade category.
     *
     * Ranking criteria (in order):
     *   1. Has an open availability slot starting soonest
     *   2. Verified
     *   3. Offers the requested trade (IssueCategory)
     *   4. Highest rating
     */
    public function find(IssueCategory $category): ?MatchResult
    {
        $contractors = Contractor::query()
            ->where('is_verified', true)
            ->whereHas('services', fn (Builder $q) => $q->where('trade', $category))
            ->with(['services' => fn ($q) => $q->where('trade', $category)])
            ->get();

        if ($contractors->isEmpty()) {
            return null;
        }

        $rated = $contractors->map(fn (Contractor $c) => $this->scoreCandidate($c, $category))
            ->sortByDesc('score')
            ->values();

        $best = $rated->first();

        return new MatchResult(
            contractor: $best['contractor'],
            service: $best['service'],
            nextSlot: $best['next_slot'],
            score: $best['score'],
        );
    }

    /**
     * Rank all contractors for a category (used by the matching API).
     *
     * @return Collection<int, array{contractor: Contractor, score: float, has_slot: bool}>
     */
    public function rankAll(IssueCategory $category): Collection
    {
        $contractors = Contractor::query()
            ->whereHas('services', fn (Builder $q) => $q->where('trade', $category))
            ->with(['services' => fn ($q) => $q->where('trade', $category)])
            ->get();

        return $contractors
            ->map(fn (Contractor $c) => $this->scoreCandidate($c, $category))
            ->sortByDesc('score')
            ->values();
    }

    private function scoreCandidate(Contractor $contractor, IssueCategory $category): array
    {
        $service = $contractor->services->first();
        $nextSlot = $contractor->openSlots()->orderBy('starts_at')->first();
        $hasSlot = $nextSlot !== null;

        $score = 0.0;

        if ($hasSlot) {
            $score += 50;
        }

        if ($contractor->is_verified) {
            $score += 25;
        }

        $score += (float) ($contractor->rating ?? 0);

        return [
            'contractor' => $contractor,
            'score' => $score,
            'has_slot' => $hasSlot,
            'next_slot' => $nextSlot,
            'service' => $service,
        ];
    }
}
