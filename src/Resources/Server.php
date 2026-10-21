<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\ServerInfo;

/**
 * @deprecated Use GlitchTip::instance()->settings() or GlitchTip::server().
 */
final readonly class Server extends Resource
{
    public function info(): ServerInfo
    {
        return new Instance($this->connector)->settings();
    }
}
