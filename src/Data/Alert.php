<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Alert
{
    /**
     * @param  list<string>  $environments
     * @param  list<array<string, mixed>>  $recipients  {id, recipientType, url, config, tagsToAdd, ...}
     */
    public function __construct(
        public ?int $id,
        public ?string $name,
        public ?int $timespanMinutes = null,
        public ?int $quantity = null,
        public bool $uptime = false,
        public array $environments = [],
        public array $recipients = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->nullableInt('id'),
            name: $a->nullableString('name'),
            timespanMinutes: $a->nullableInt('timespanMinutes'),
            quantity: $a->nullableInt('quantity'),
            uptime: $a->bool('uptime'),
            environments: $a->strings('environments'),
            recipients: $a->objects('alertRecipients'),
        );
    }
}
