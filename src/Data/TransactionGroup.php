<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class TransactionGroup
{
    /**
     * Durations are in milliseconds.
     */
    public function __construct(
        public int $id,
        public int $projectId,
        public string $transaction,
        public string $op,
        public ?string $method,
        public int $count,
        public float $avgDuration,
        public ?float $p50,
        public ?float $p95,
        public int $errorCount,
        public float $errorRate,
        public ?float $throughput,
        public CarbonImmutable $firstSeen,
        public CarbonImmutable $lastSeen,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->int('id'),
            projectId: $a->int('project'),
            transaction: $a->string('transaction'),
            op: $a->string('op'),
            method: $a->nullableString('method'),
            count: $a->int('count'),
            avgDuration: $a->float('avgDuration'),
            p50: $a->nullableFloat('p50'),
            p95: $a->nullableFloat('p95'),
            errorCount: $a->int('errorCount'),
            errorRate: $a->float('errorRate'),
            throughput: $a->nullableFloat('throughput'),
            firstSeen: $a->date('firstSeen'),
            lastSeen: $a->date('lastSeen'),
        );
    }
}
