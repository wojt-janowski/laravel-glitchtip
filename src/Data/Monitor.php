<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Enums\MonitorType;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Monitor
{
    /**
     * @param  list<MonitorCheck>  $checks  most recent checks, newest first
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public ?MonitorType $type,
        public ?string $url,
        public ?bool $isUp,
        public CarbonImmutable $created,
        public ?CarbonImmutable $lastChange = null,
        public ?string $projectId = null,
        public ?string $projectName = null,
        public ?int $environmentId = null,
        public ?string $environmentName = null,
        public ?string $heartbeatEndpoint = null,
        public ?string $endpointId = null,
        public int $interval = 60,
        public ?int $timeout = null,
        public ?int $expectedStatus = null,
        public ?string $expectedBody = null,
        public int $confirmationThreshold = 1,
        public array $checks = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->nullableInt('id'),
            name: $a->string('name'),
            type: $a->enum('monitorType', MonitorType::class),
            url: $a->nullableString('url'),
            isUp: $a->nullableBool('isUp'),
            created: $a->date('created'),
            lastChange: $a->nullableDate('lastChange'),
            projectId: $a->nullableString('projectID'),
            projectName: $a->nullableString('projectName'),
            environmentId: $a->nullableInt('environmentID'),
            environmentName: $a->nullableString('envName'),
            heartbeatEndpoint: $a->nullableString('heartbeatEndpoint'),
            endpointId: $a->nullableString('endpointID'),
            interval: $a->int('interval', 60),
            timeout: $a->nullableInt('timeout'),
            expectedStatus: $a->nullableInt('expectedStatus'),
            expectedBody: $a->nullableString('expectedBody'),
            confirmationThreshold: $a->int('confirmationThreshold', 1),
            checks: array_map(MonitorCheck::fromArray(...), $a->objects('checks')),
        );
    }
}
