<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * The API token a request authenticated with. The secret itself is never exposed.
 */
final readonly class ApiToken
{
    /**
     * @param  list<string>  $scopes  e.g. ["project:read", "event:write"]
     */
    public function __construct(
        public int $id,
        public string $label,
        public CarbonImmutable $created,
        public array $scopes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->int('id'),
            label: $a->string('label'),
            created: $a->date('created'),
            scopes: $a->strings('scopes'),
        );
    }
}
