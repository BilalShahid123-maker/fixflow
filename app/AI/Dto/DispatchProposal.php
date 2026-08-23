<?php

namespace App\AI\Dto;

use App\Enums\AuthorityLevel;
use App\Enums\Severity;

readonly class DispatchProposal
{
    public function __construct(
        public string $actionType,
        public int $estimatedCostCents,
        public Severity $severity,
        public float $confidence,
        public ?bool $contractorVerified = null,
        public AuthorityLevel $requestedLevel = AuthorityLevel::Execute,
        public array $payload = [],
    ) {}
}
