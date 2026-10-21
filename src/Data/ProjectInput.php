<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

/**
 * The definition of a project, used to create or update one.
 */
final readonly class ProjectInput
{
    /**
     * @param  string|null  $slug  generated from the name when omitted
     * @param  string|null  $platform  e.g. "php-laravel" or "javascript-react"
     * @param  int|null  $eventThrottleRate  percentage of incoming events to drop
     */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $platform = null,
        public ?int $eventThrottleRate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'platform' => $this->platform,
            'eventThrottleRate' => $this->eventThrottleRate,
        ], fn (mixed $value): bool => $value !== null);
    }
}
