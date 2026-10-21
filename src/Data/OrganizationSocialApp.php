<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * An organization's own single sign-on provider.
 */
final readonly class OrganizationSocialApp
{
    public function __construct(
        public string $name,
        public string $provider,
        public string $clientId,
        public string $brand,
        public string $loginUrl,
        public ?int $id = null,
        public ?string $providerId = null,
        public ?string $serverUrl = null,
        public ?string $callbackUrl = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            name: $a->string('name'),
            provider: $a->string('provider'),
            clientId: $a->string('clientID'),
            brand: $a->string('brand'),
            loginUrl: $a->string('loginUrl'),
            id: $a->nullableInt('id'),
            providerId: $a->nullableString('providerID'),
            serverUrl: $a->nullableString('serverUrl'),
            callbackUrl: $a->nullableString('callbackUrl'),
        );
    }
}
