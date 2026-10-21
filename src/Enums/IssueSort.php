<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum IssueSort: string
{
    case LastSeen = 'last_seen';
    case FirstSeen = 'first_seen';
    case Count = 'count';
    case Priority = 'priority';
    case LastSeenDesc = '-last_seen';
    case FirstSeenDesc = '-first_seen';
    case CountDesc = '-count';
    case PriorityDesc = '-priority';
}
