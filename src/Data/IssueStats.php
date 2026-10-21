<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * Event counts over time for one issue.
 */
final readonly class IssueStats
{
    /**
     * @param  array<string, list<array{0: int, 1: int}>>  $series  [timestamp, count] pairs keyed by period ("24h" or "14d")
     */
    public function __construct(
        public string $id,
        public int $count,
        public int $userCount,
        public bool $isUnhandled,
        public ?CarbonImmutable $firstSeen = null,
        public ?CarbonImmutable $lastSeen = null,
        public array $series = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);
        $series = [];

        foreach ($a->array('stats') as $period => $points) {
            if (! is_array($points)) {
                continue;
            }

            $series[(string) $period] = array_values(array_map(
                fn (array $point): array => [(int) ($point[0] ?? 0), (int) ($point[1] ?? 0)],
                array_filter($points, is_array(...)),
            ));
        }

        return new self(
            id: $a->string('id'),
            count: $a->int('count'),
            userCount: $a->int('userCount'),
            isUnhandled: $a->bool('isUnhandled'),
            firstSeen: $a->nullableDate('firstSeen'),
            lastSeen: $a->nullableDate('lastSeen'),
            series: $series,
        );
    }
}
