<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

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
            'interval' => $interval,
            'start' => $start,
            'end' => $end,
            'project' => $projects,
        ]);

        return StatsSeries::fromArray(self::object($response), $field->value);
    }
}
