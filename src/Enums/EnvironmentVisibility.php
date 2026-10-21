<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum EnvironmentVisibility: string
{
    case All = 'all';
    case Hidden = 'hidden';
    case Visible = 'visible';
}
