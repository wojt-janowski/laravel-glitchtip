<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * An environment as configured for one project.
 */
final readonly class ProjectEnvironment
{
    public function __construct(
        public string $name,
        public ?int $id = null,
        public bool $isHidden = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            name: $a->string('name'),
            id: $a->nullableInt('id'),
            isHidden: $a->bool('isHidden'),
        );
    }
}
