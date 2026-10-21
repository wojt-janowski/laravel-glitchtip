<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum IssueStatsPeriod: string
{
    case Day = '24h';
    case TwoWeeks = '14d';
}
