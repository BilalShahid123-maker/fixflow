<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PendingTriage = 'pending_triage';
    case Triaged = 'triaged';
    case AwaitingApproval = 'awaiting_approval';
    case Dispatching = 'dispatching';
    case Dispatched = 'dispatched';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Rejected], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingTriage => 'Pending triage',
            self::Triaged => 'Triaged',
            self::AwaitingApproval => 'Awaiting approval',
            self::Dispatching => 'Dispatching',
            self::Dispatched => 'Dispatched',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
        };
    }
}
