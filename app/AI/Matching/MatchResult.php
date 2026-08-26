<?php

namespace App\AI\Matching;

use App\Models\Contractor;
use App\Models\ContractorAvailability;
use App\Models\ContractorService;

readonly class MatchResult
{
    public function __construct(
        public Contractor $contractor,
        public ?ContractorService $service,
        public ?ContractorAvailability $nextSlot,
        public float $score,
    ) {}
}
