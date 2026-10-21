<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum MonitorSort: string
{
    case Created = 'created';
    case Name = 'name';
    case Project = 'project';
    case Status = 'status';
    case CreatedDesc = '-created';
    case NameDesc = '-name';
    case ProjectDesc = '-project';
    case StatusDesc = '-status';
}
