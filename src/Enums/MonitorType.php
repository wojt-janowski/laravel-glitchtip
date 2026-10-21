<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum MonitorType: string
{
    case Ping = 'Ping';
    case Get = 'GET';
    case Post = 'POST';
    case TcpPort = 'TCP Port';
    case Ssl = 'SSL';
    case Heartbeat = 'Heartbeat';
}
