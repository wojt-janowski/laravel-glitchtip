<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum IssueType: string
{
    case Default = 'default';
    case Error = 'error';
    case Csp = 'csp';
}
