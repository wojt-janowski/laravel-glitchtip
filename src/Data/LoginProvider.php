<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A social sign-in option shown on a login page.
 */
final readonly class LoginProvider
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public string $name,
        public string $brand,
        public string $provider,
        public string $clientId,
        public array $scopes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            name: $a->string('name'),
            brand: $a->string('brand'),
            provider: $a->string('provider'),
            clientId: $a->string('client_id'),
            scopes: $a->strings('scopes'),
        );
    }
}
