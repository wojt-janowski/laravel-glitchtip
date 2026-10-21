<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class ProjectKey
{
    /**
     * @param  array<string, string>  $dsnUrls  every DSN variant GlitchTip returns (public, secret, security, csp, ...)
     */
    public function __construct(
        public string $id,
        public string $publicKey,
        public int $projectId,
        public ?string $label,
        public ?string $dsn,
        public CarbonImmutable $dateCreated,
        public array $dsnUrls = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        $dsnUrls = array_map(strval(...), array_filter($a->array('dsn'), is_string(...)));

        return new self(
            id: $a->string('id'),
            publicKey: $a->string('public'),
            projectId: $a->int('projectID'),
            label: $a->nullableString('label') ?? $a->nullableString('name'),
            dsn: $dsnUrls['public'] ?? null,
            dateCreated: $a->date('dateCreated'),
            dsnUrls: $dsnUrls,
        );
    }
}
