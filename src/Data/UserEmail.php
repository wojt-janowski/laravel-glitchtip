<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class UserEmail
{
    public function __construct(
        public string $email,
        public bool $isPrimary,
        public bool $isVerified,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            email: $a->string('email'),
            isPrimary: $a->bool('isPrimary'),
            isVerified: $a->bool('isVerified'),
        );
    }
}
