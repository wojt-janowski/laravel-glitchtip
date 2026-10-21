<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A debug information file (dSYM, ProGuard mapping, ...) stored for a project.
 */
final readonly class DebugFile
{
    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $id,
        public string $cpuName,
        public string $objectName,
        public int $size,
        public string $sha1,
        public CarbonImmutable $dateCreated,
        public ?string $debugId = null,
        public ?string $symbolType = null,
        public array $headers = [],
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        /** @var array<string, mixed> $headers */
        $headers = $a->array('headers');

        /** @var array<string, mixed> $extra */
        $extra = $a->array('data');

        return new self(
            id: $a->string('id'),
            cpuName: $a->string('cpuName'),
            objectName: $a->string('objectName'),
            size: $a->int('size'),
            sha1: $a->string('sha1'),
            dateCreated: $a->date('dateCreated'),
            debugId: $a->nullableString('debugId'),
            symbolType: $a->nullableString('symbolType'),
            headers: $headers,
            data: $extra,
        );
    }
}
