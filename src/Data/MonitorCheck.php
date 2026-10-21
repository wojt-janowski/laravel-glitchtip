<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class MonitorCheck
{
    /**
     * @param  int|null  $responseTime  in milliseconds, present on detail and checks responses
     */
    public function __construct(
        public CarbonImmutable $startedAt,
        public bool $isUp,
        public ?int $reason = null,
        public ?int $responseTime = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        // The heartbeat endpoint answers in snake_case, unlike the rest of the API.
        return new self(
            startedAt: $a->has('startCheck') ? $a->date('startCheck') : $a->date('start_check'),
            isUp: $a->nullableBool('isUp') ?? $a->bool('is_up'),
            reason: $a->nullableInt('reason'),
            responseTime: $a->nullableInt('responseTime') ?? $a->nullableInt('response_time'),
        );
    }
}
