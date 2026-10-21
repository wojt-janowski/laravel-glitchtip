<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class IssueTagValue
{
    public function __construct(
        public string $key,
        public string $name,
        public string $value,
        public int $count,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            key: $a->string('key'),
            name: $a->string('name'),
            value: $a->string('value'),
            count: $a->int('count'),
        );
    }
}
