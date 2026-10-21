<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * An artifact (source map, minified source, ...) attached to a release.
 */
final readonly class ReleaseFile
{
    /**
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        public string $id,
        public string $name,
        public CarbonImmutable $dateCreated,
        public int $size = 0,
        public ?string $sha1 = null,
        public array $headers = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        /** @var array<string, mixed> $headers */
        $headers = $a->array('headers');

        return new self(
            id: $a->string('id'),
            name: $a->string('name'),
            dateCreated: $a->date('dateCreated'),
            size: $a->int('size'),
            sha1: $a->nullableString('sha1'),
            headers: $headers,
        );
    }
}
