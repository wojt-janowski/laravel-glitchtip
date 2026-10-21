<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum StatsInterval: string
{
    case Day = '1d';
    case Hour = '1h';
    case Minute = '1m';
}
