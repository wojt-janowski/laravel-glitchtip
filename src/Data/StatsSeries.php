<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * An organization's event volume over time, from the stats v2 endpoint.
 */
final readonly class StatsSeries
{
    /**
     * @param  list<CarbonImmutable>  $intervals  bucket start times
     * @param  list<int|null>  $values  one per interval; null where GlitchTip has no data
     */
    public function __construct(
        public string $field,
        public array $intervals,
        public array $values,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $field): self
    {
        $a = Attributes::of($data);
        $group = Attributes::of($a->objects('groups')[0] ?? []);
        $series = Attributes::of($group->nullableObject('series') ?? []);

        return new self(
            field: $field,
            intervals: array_map(CarbonImmutable::parse(...), $a->strings('intervals')),
            values: array_values(array_map(
                fn (mixed $value): ?int => is_numeric($value) ? (int) $value : null,
                $series->array($field),
            )),
        );
    }

    /**
     * Values keyed by ISO 8601 interval start.
     *
     * @return array<string, int|null>
     */
    public function toMap(): array
    {
        $map = [];

        foreach ($this->intervals as $index => $interval) {
            $map[$interval->toIso8601String()] = $this->values[$index] ?? null;
        }

        return $map;
    }
}
