<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A span repeated many times within single transactions, typically an N+1 query.
 */
final readonly class NPlusOnePattern
{
    public function __construct(
        public string $transactionName,
        public string $op,
        public string $description,
        public int $totalSpans,
        public int $transactionCount,
        public float $spansPerTransaction,
        public float $avgDuration,
        public float $totalTime,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            transactionName: $a->string('transactionName'),
            op: $a->string('op'),
            description: $a->string('description'),
            totalSpans: $a->int('totalSpans'),
            transactionCount: $a->int('transactionCount'),
            spansPerTransaction: $a->float('spansPerTxn'),
            avgDuration: $a->float('avgDuration'),
            totalTime: $a->float('totalTime'),
        );
    }
}
