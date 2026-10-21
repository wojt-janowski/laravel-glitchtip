<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class SpanGroup
{
    /**
     * Durations are in milliseconds.
     */
    public function __construct(
        public string $op,
        public string $description,
        public int $count,
        public float $avgDuration,
        public float $p95Duration,
        public float $totalTime,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            op: $a->string('op'),
            description: $a->string('description'),
            count: $a->int('count'),
            avgDuration: $a->float('avgDuration'),
            p95Duration: $a->float('p95Duration'),
            totalTime: $a->float('totalTime'),
        );
    }
}
