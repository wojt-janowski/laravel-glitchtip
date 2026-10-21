<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class LogEntry
{
    /**
     * @param  array<string, mixed>  $data  structured attributes attached to the log line
     */
    public function __construct(
        public string $id,
        public CarbonImmutable $timestamp,
        public string $level,
        public string $body,
        public int $projectId,
        public ?string $service = null,
        public ?string $environment = null,
        public ?string $host = null,
        public ?string $traceId = null,
        public ?string $spanId = null,
        public ?int $severityNumber = null,
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        /** @var array<string, mixed> $attributes */
        $attributes = $a->array('data');

        return new self(
            id: $a->string('id'),
            timestamp: $a->date('timestamp'),
            level: $a->string('level'),
            body: $a->string('body'),
            projectId: $a->int('projectId'),
            service: $a->nullableString('service'),
            environment: $a->nullableString('environment'),
            host: $a->nullableString('host'),
            traceId: $a->nullableString('traceID'),
            spanId: $a->nullableString('spanID'),
            severityNumber: $a->nullableInt('severityNumber'),
            data: $attributes,
        );
    }
}
