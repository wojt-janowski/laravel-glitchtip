<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Exceptions;

final class MissingConfigurationException extends GlitchTipException
{
    public static function organization(): self
    {
        return new self('No GlitchTip organization is configured. Set GLITCHTIP_ORGANIZATION or call GlitchTip::forOrganization().');
    }

    public static function token(): self
    {
        return new self('No GlitchTip API token is configured. Set GLITCHTIP_TOKEN.');
    }
}
