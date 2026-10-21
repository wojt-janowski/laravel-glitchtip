<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use WojtJanowski\GlitchTip\Data\StatsSeries;
use WojtJanowski\GlitchTip\Enums\StatsCategory;
use WojtJanowski\GlitchTip\Enums\StatsField;
use WojtJanowski\GlitchTip\Enums\StatsInterval;

/**
 * Organization-wide event volume, as used by the Sentry Grafana integration.
 */
final readonly class Stats extends Resource
{
    /**
     * Events per interval between two times. GlitchTip returns at most 1000
     * intervals, about 41 days of hourly data.
     *
     * Daily series are added up from hourly buckets: GlitchTip 6.2's stats_v2
     * answers interval=1d with only the first hour of each day, not its total.
     *
     * @param  list<int>  $projects  project ids; every project when empty
     */
    public function series(
        DateTimeInterface $start,
        DateTimeInterface $end,
        StatsCategory $category = StatsCategory::Error,
        StatsField $field = StatsField::SumQuantity,
        StatsInterval $interval = StatsInterval::Hour,
        array $projects = [],
    ): StatsSeries {
        $response = $this->connector->get("organizations/{$this->organization()}/stats_v2/", [
            'category' => $category,
            'field' => $field,
            'interval' => $interval === StatsInterval::Day ? StatsInterval::Hour : $interval,
            'start' => $start,
            'end' => $end,
            'project' => $projects,
        ]);

        $series = StatsSeries::fromArray(self::object($response), $field->value);

        return $interval === StatsInterval::Day ? $this->daily($series) : $series;
    }

    /**
     * Hourly values added up per UTC day. A day stays null only when every
     * hour in it is.
     */
    private function daily(StatsSeries $hours): StatsSeries
    {
        $days = [];

        foreach ($hours->intervals as $index => $hour) {
            $day = $hour->utc()->startOfDay()->toIso8601String();
            $value = $hours->values[$index] ?? null;
            $days[$day] = $value === null ? ($days[$day] ?? null) : ($days[$day] ?? 0) + $value;
        }

        return new StatsSeries(
            field: $hours->field,
            intervals: array_map(CarbonImmutable::parse(...), array_keys($days)),
            values: array_values($days),
        );
    }
}
