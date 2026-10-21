<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum StatsField: string
{
    case SumQuantity = 'sum(quantity)';
    case SumTimesSeen = 'sum(times_seen)';
}
