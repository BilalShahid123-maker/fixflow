<?php

namespace App\Enums;

enum AuthorityLevel: int
{
    case Read = 0;
    case Recommend = 1;
    case Prepare = 2;
    case Execute = 3;

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Read',
            self::Recommend => 'Recommend',
            self::Prepare => 'Prepare',
            self::Execute => 'Execute',
        };
    }

    public function atLeast(self $other): bool
    {
        return $this->value >= $other->value;
    }
}
