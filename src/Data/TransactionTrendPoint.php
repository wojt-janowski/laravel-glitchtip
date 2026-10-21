<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class TransactionTrendPoint
{
    public function __construct(
        public CarbonImmutable $date,
        public int $count,
        public int $transactionCount,
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
            date: $a->date('date'),
            count: $a->int('count'),
            transactionCount: $a->int('transactionCount'),
            avgDuration: $a->float('avgDuration'),
            totalTime: $a->float('totalTime'),
        );
    }
}
