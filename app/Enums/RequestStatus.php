<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PendingTriage = 'pending_triage';
    case Triaged = 'triaged';
    case AwaitingApproval = 'awaiting_approval';
    case Dispatched = 'dispatched';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Rejected], true);
    }
}
