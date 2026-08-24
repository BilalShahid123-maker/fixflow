<?php

namespace App\AI\Dto;

use App\Enums\IssueCategory;
use App\Enums\Severity;

readonly class TriageResult
{
    public function __construct(
        public IssueCategory $category,
        public Severity $severity,
        public float $confidence,
        public bool $isEmergency,
        public string $reasoning,
        public ?array $meta = null,
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->category->value,
            'severity' => $this->severity->value,
            'confidence' => $this->confidence,
            'is_emergency' => $this->isEmergency,
            'reasoning' => $this->reasoning,
            'meta' => $this->meta,
        ];
    }
}
