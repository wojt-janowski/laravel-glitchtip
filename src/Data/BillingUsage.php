<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * Event usage for one billing period.
 */
final readonly class BillingUsage
{
    public function __construct(
        public int $total,
        public int $eventCount,
        public int $transactionEventCount,
        public float $uptimeCheckEventCount,
        public float $logEventCount,
        public int $fileSizeMb,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            total: $a->int('total'),
            eventCount: $a->int('eventCount'),
            transactionEventCount: $a->int('transactionEventCount'),
            uptimeCheckEventCount: $a->float('uptimeCheckEventCount'),
            logEventCount: $a->float('logEventCount'),
            fileSizeMb: $a->int('fileSizeMb'),
        );
    }
}
