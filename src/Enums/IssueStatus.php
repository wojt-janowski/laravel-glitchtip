<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum IssueStatus: string
{
    case Unresolved = 'unresolved';
    case Resolved = 'resolved';
    case Ignored = 'ignored';
}
