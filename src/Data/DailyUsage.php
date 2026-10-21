<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class DailyUsage
{
    public function __construct(
        public CarbonImmutable $date,
        public int $eventCount,
        public int $transactionEventCount,
        public int $uptimeCheckEventCount,
        public int $logEventCount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            date: $a->date('date'),
            eventCount: $a->int('eventCount'),
            transactionEventCount: $a->int('transactionEventCount'),
            uptimeCheckEventCount: $a->int('uptimeCheckEventCount'),
            logEventCount: $a->int('logEventCount'),
        );
    }
}
