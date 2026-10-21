<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Organization
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public CarbonImmutable $dateCreated,
        public bool $isAcceptingEvents = true,
        public bool $require2fa = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            slug: $a->string('slug'),
            name: $a->string('name'),
            dateCreated: $a->date('dateCreated'),
            isAcceptingEvents: $a->bool('isAcceptingEvents', true),
            require2fa: $a->bool('require2fa'),
        );
    }
}
