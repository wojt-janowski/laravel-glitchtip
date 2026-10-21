<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A known log service, environment or host, for building filters.
 */
final readonly class LogResource
{
    public function __construct(
        public string $name,
        public string $type,
        public CarbonImmutable $lastSeen,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            name: $a->string('name'),
            type: $a->string('type'),
            lastSeen: $a->date('lastSeen'),
        );
    }
}
