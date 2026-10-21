<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

enum SocialAppProvider: string
{
    case OpenIdConnect = 'openid_connect';
    case Google = 'google';
}
