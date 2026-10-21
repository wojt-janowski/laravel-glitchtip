<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Enums\MonitorType;

/**
 * The full definition of an uptime monitor, used to create or replace one.
 */
final readonly class MonitorInput
{
    /**
     * @param  int  $interval  seconds between checks
     * @param  int|null  $timeout  seconds before a check fails
     * @param  string|null  $project  project id the monitor reports against
     */
    public function __construct(
        public string $name,
        public MonitorType $type = MonitorType::Get,
        public ?string $url = null,
        public int $interval = 60,
        public ?int $timeout = null,
        public ?int $expectedStatus = 200,
        public string $expectedBody = '',
        public int $confirmationThreshold = 1,
        public ?string $project = null,
        public ?int $environmentId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'monitorType' => $this->type->value,
            'url' => $this->url,
            'interval' => $this->interval,
            'timeout' => $this->timeout,
            'expectedStatus' => $this->expectedStatus,
            'expectedBody' => $this->expectedBody,
            'confirmationThreshold' => $this->confirmationThreshold,
            'project' => $this->project,
            'environmentID' => $this->environmentId,
        ];
    }
}
