<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum SpanGroupSort: string
{
    case TotalTime = 'total_time';
    case AverageDuration = 'avg_duration';
    case Count = 'count';
    case TotalTimeDesc = '-total_time';
    case AverageDurationDesc = '-avg_duration';
    case CountDesc = '-count';
}
