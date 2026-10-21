<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class StatusPage
{
    /**
     * @param  list<Monitor>  $monitors
     */
    public function __construct(
        public string $name,
        public bool $isPublic,
        public ?string $slug = null,
        public array $monitors = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            name: $a->string('name'),
            isPublic: $a->bool('isPublic'),
            slug: $a->nullableString('slug'),
            monitors: $a->mapEach('monitors', Monitor::fromArray(...)),
        );
    }
}
