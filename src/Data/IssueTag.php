<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class IssueTag
{
    /**
     * @param  list<IssueTagValue>  $topValues
     */
    public function __construct(
        public string $key,
        public string $name,
        public int $totalValues,
        public int $uniqueValues,
        public array $topValues = [],
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
            totalValues: $a->int('totalValues'),
            uniqueValues: $a->int('uniqueValues'),
            topValues: array_map(IssueTagValue::fromArray(...), $a->objects('topValues')),
        );
    }
}
