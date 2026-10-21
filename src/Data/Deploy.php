<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Deploy
{
    public function __construct(
        public ?int $id,
        public string $environment,
        public CarbonImmutable $dateCreated,
        public ?CarbonImmutable $dateStarted = null,
        public ?CarbonImmutable $dateFinished = null,
        public ?string $url = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->nullableInt('id'),
            environment: $a->string('environment'),
            dateCreated: $a->date('dateCreated'),
            dateStarted: $a->nullableDate('dateStarted'),
            dateFinished: $a->nullableDate('dateFinished'),
            url: $a->nullableString('url'),
        );
    }
}
