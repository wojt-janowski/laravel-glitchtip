<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class AlertTestResult
{
    public function __construct(
        public string $recipientType,
        public string $status,
        public ?string $message = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            recipientType: $a->string('recipientType'),
            status: $a->string('status'),
            message: $a->nullableString('message'),
        );
    }
}
