<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Enums\SubscriptionStatus;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class BillingSubscription
{
    public function __construct(
        public string $id,
        public string $collectionMethod,
        public BillingProduct $product,
        public BillingPrice $price,
        public CarbonImmutable $created,
        public CarbonImmutable $currentPeriodStart,
        public CarbonImmutable $currentPeriodEnd,
        public CarbonImmutable $startDate,
        public ?SubscriptionStatus $status = null,
        public ?CarbonImmutable $cycleStart = null,
        public ?CarbonImmutable $cycleEnd = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('stripeID'),
            collectionMethod: $a->string('collectionMethod'),
            product: BillingProduct::fromArray($a->nullableObject('product') ?? []),
            price: BillingPrice::fromArray($a->nullableObject('price') ?? []),
            created: $a->date('created'),
            currentPeriodStart: $a->date('currentPeriodStart'),
            currentPeriodEnd: $a->date('currentPeriodEnd'),
            startDate: $a->date('startDate'),
            status: $a->enum('status', SubscriptionStatus::class),
            cycleStart: $a->nullableDate('subscriptionCycleStart'),
            cycleEnd: $a->nullableDate('subscriptionCycleEnd'),
        );
    }
}
