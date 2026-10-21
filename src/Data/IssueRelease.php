<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class IssueRelease
{
    public function __construct(
        public string $version,
        public string $shortVersion,
        public CarbonImmutable $dateCreated,
        public ?CarbonImmutable $dateReleased = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            version: $a->string('version'),
            shortVersion: $a->string('shortVersion'),
            dateCreated: $a->date('dateCreated'),
            dateReleased: $a->nullableDate('dateReleased'),
        );
    }
}
