<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\ServerInfo;

final readonly class Server extends Resource
{
    public function info(): ServerInfo
    {
        return ServerInfo::fromArray(self::object($this->connector->get('/api/settings/')));
    }
}
