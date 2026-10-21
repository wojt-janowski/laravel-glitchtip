<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A source repository linked to the organization, for release commits.
 */
final readonly class Repository
{
    /**
     * @param  array<string, mixed>|null  $provider
     */
    public function __construct(
        public string $id,
        public string $name,
        public CarbonImmutable $dateCreated,
        public ?string $url = null,
        public ?string $status = null,
        public ?array $provider = null,
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
            dateCreated: $a->date('dateCreated'),
            url: $a->nullableString('url'),
            status: $a->nullableString('status'),
            provider: $a->nullableObject('provider'),
        );
    }
}
