<?php

namespace App\Enums;

enum IssueCategory: string
{
    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case Hvac = 'hvac';
    case Appliance = 'appliance';
    case Structural = 'structural';
    case General = 'general';
}
