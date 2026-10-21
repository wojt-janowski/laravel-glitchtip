<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * Metered overage billing: whether it is on, its cap, and usage against it.
 */
final readonly class Overage
{
    /**
     * @param  int  $throttleRate  percentage of events currently dropped
     */
    public function __construct(
        public bool $enabled,
        public bool $eligible,
        public bool $configured,
        public bool $active,
        public int $capCents,
        public int $maxCapCents,
        public int $capUnits,
        public int $quota,
        public int $usage,
        public int $overageUnits,
        public int $overageCostCents,
        public int $throttleRate,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            enabled: $a->bool('enabled'),
            eligible: $a->bool('eligible'),
            configured: $a->bool('configured'),
            active: $a->bool('active'),
            capCents: $a->int('capCents'),
            maxCapCents: $a->int('maxCapCents'),
            capUnits: $a->int('capUnits'),
            quota: $a->int('quota'),
            usage: $a->int('usage'),
            overageUnits: $a->int('overageUnits'),
            overageCostCents: $a->int('overageCostCents'),
            throttleRate: $a->int('throttleRate'),
        );
    }
}
