<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Exceptions;

/**
 * Thrown when the GlitchTip instance is too old for the requested endpoint.
 */
final class UnsupportedByServerException extends GlitchTipException
{
    public function __construct(
        public readonly string $feature,
        public readonly string $requiredVersion,
        public readonly string $serverVersion,
    ) {
        parent::__construct(sprintf(
            '%s needs GlitchTip %s or newer; this server runs %s.',
            $feature,
            $requiredVersion,
            $serverVersion === '' ? 'an unknown version' : $serverVersion,
        ));
    }
}
