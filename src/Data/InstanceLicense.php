<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class InstanceLicense
{
    public function __construct(
        public string $billingEmail,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            billingEmail: $a->string('billingEmail'),
        );
    }
}
