<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Commit
{
    public function __construct(
        public string $id,
        public ?string $message = null,
        public ?string $authorName = null,
        public ?string $authorEmail = null,
        public ?CarbonImmutable $dateCreated = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            message: $a->nullableString('message'),
            authorName: $a->nullableString('authorName'),
            authorEmail: $a->nullableString('authorEmail'),
            dateCreated: $a->nullableDate('dateCreated'),
        );
    }
}
