<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum TransactionGroupSort: string
{
    case Created = 'created';
    case AverageDuration = 'avg_duration';
    case Count = 'count';
    case CreatedDesc = '-created';
    case AverageDurationDesc = '-avg_duration';
    case CountDesc = '-count';
}
