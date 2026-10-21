<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A social login (GitHub, Google, ...) linked to a user.
 */
final readonly class SocialIdentity
{
    public function __construct(
        public string $provider,
        public string $uid,
        public CarbonImmutable $dateJoined,
        public ?CarbonImmutable $lastLogin = null,
        public ?int $id = null,
        public ?string $email = null,
        public ?string $username = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            provider: $a->string('provider'),
            uid: $a->string('uid'),
            dateJoined: $a->date('dateJoined'),
            lastLogin: $a->nullableDate('lastLogin'),
            id: $a->nullableInt('id'),
            email: $a->nullableString('email'),
            username: $a->nullableString('username'),
        );
    }
}
