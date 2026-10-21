<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class ProjectReference
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $slug = null,
        public ?string $platform = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            name: $a->string('name'),
            slug: $a->nullableString('slug'),
            platform: $a->nullableString('platform'),
        );
    }
}
