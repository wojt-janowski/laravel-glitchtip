<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class BillingPrice
{
    /**
     * @param  string  $price  as Stripe formats it
     */
    public function __construct(
        public string $id,
        public string $price,
        public string $interval,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('stripeID'),
            price: $a->string('price'),
            interval: $a->string('interval'),
        );
    }
}
