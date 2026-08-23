<?php

namespace App\Enums;

enum ActionStatus: string
{
    case Proposed = 'proposed';
    case NeedsApproval = 'needs_approval';
    case Approved = 'approved';
    case Executed = 'executed';
    case Blocked = 'blocked';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Executed, self::Blocked], true);
    }
}
