<?php

declare(strict_types=1);

namespace App\Enums;

enum PeriodStatus: string
{
    case Active = 'ACTIVE';
    case Expired = 'EXPIRED';
    case Pending = 'PENDING';
    case NoPeriod = 'NO_PERIOD';
}
