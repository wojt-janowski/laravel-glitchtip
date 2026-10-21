<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum StatsCategory: string
{
    case Error = 'error';
    case Transaction = 'transaction';
}
