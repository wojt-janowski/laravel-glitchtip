<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class User
{
    /**
     * @param  list<SocialIdentity>  $identities
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $username,
        public CarbonImmutable $dateJoined,
        public bool $hasPasswordAuth,
        public UserOptions $options,
        public ?string $name = null,
        public ?CarbonImmutable $lastLogin = null,
        public bool $isSuperuser = false,
        public bool $isActive = true,
        public array $identities = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            email: $a->string('email'),
            username: $a->string('username'),
            dateJoined: $a->date('dateJoined'),
            hasPasswordAuth: $a->bool('hasPasswordAuth'),
            options: UserOptions::fromArray($a->nullableObject('options') ?? []),
            name: $a->nullableString('name'),
            lastLogin: $a->nullableDate('lastLogin'),
            isSuperuser: $a->bool('isSuperuser'),
            isActive: $a->bool('isActive', true),
            identities: $a->mapEach('identities', SocialIdentity::fromArray(...)),
        );
    }
}
