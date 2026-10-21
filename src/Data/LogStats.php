<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class LogStats
{
    /**
     * @param  list<CarbonImmutable>  $intervals  bucket start times
     * @param  array<string, list<int>>  $series  counts per bucket, keyed by series name (usually the level)
     */
    public function __construct(
        public array $intervals,
        public array $series,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        $intervals = array_map(
            CarbonImmutable::parse(...),
            $a->strings('intervals'),
        );

        $series = [];

        foreach ($a->objects('series') as $row) {
            $rowAttributes = Attributes::of($row);
            $series[$rowAttributes->string('name')] = array_values(array_map(intval(...), $rowAttributes->array('data')));
        }

        return new self(intervals: $intervals, series: $series);
    }
}
