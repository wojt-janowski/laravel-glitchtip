<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class BillingProduct
{
    /**
     * @param  int  $events  monthly event quota
     * @param  list<BillingPrice>  $prices
     * @param  list<string>  $marketingFeatures
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $events,
        public ?BillingPrice $defaultPrice = null,
        public ?string $defaultPriceId = null,
        public array $prices = [],
        public array $marketingFeatures = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('stripeID'),
            name: $a->string('name'),
            description: $a->string('description'),
            events: $a->int('events'),
            defaultPrice: $a->map('defaultPrice', BillingPrice::fromArray(...)),
            defaultPriceId: $a->nullableString('default_price_id'),
            prices: $a->mapEach('prices', BillingPrice::fromArray(...)),
            marketingFeatures: $a->strings('marketingFeatures'),
        );
    }
}
