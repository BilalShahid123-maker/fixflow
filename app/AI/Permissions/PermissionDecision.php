<?php

namespace App\AI\Permissions;

use App\AI\Dto\DispatchProposal;
use App\Enums\ActionStatus;
use App\Enums\AuthorityLevel;

readonly class PermissionDecision
{
    public function __construct(
        public DispatchProposal $proposal,
        public AuthorityLevel $grantedLevel,
        public ActionStatus $status,
        public array $reasons,
    ) {}

    public function mayAutoExecute(): bool
    {
        return $this->status === ActionStatus::Approved
            && $this->grantedLevel->atLeast(AuthorityLevel::Execute);
    }
}
