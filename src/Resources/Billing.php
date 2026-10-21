<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\BillingProduct;
use WojtJanowski\GlitchTip\Data\BillingSubscription;
use WojtJanowski\GlitchTip\Data\BillingUsage;
use WojtJanowski\GlitchTip\Data\DailyUsage;
use WojtJanowski\GlitchTip\Data\Overage;

/**
 * Event usage and Stripe billing. Usage, daily usage and overage status work
 * on self-hosted instances too, from GlitchTip 6.1.3, 6.1.4 and 6.2.1
 * respectively; older servers answer 404. Plans, subscriptions, checkout and
 * the portal need Stripe billing (as on app.glitchtip.com); without it there
 * are no products and no subscription.
 */
final readonly class Billing extends Resource
{
    /**
     * @return list<BillingProduct>
     */
    public function products(): array
    {
        return self::listOf($this->connector->get('stripe/products/'), BillingProduct::fromArray(...));
    }

    /**
     * The organization's subscription, or null without one.
     */
    public function subscription(): ?BillingSubscription
    {
        $subscription = self::object($this->connector->get("stripe/subscriptions/{$this->organization()}/"));

        return $subscription === [] ? null : BillingSubscription::fromArray($subscription);
    }

    /**
     * Subscribe the organization to a price directly. Stripe only allows
     * this for free prices; paid plans go through checkout().
     */
    public function subscribe(string $price): BillingSubscription
    {
        return BillingSubscription::fromArray((array) $this->connector->post('stripe/subscriptions/', [
            'price' => $price,
            'organization' => rawurldecode($this->organization()),
        ])->json('subscription', []));
    }

    /**
     * A Stripe Checkout URL to send the user to for a paid plan.
     */
    public function checkout(string $price): string
    {
        return (string) $this->connector->post(
            "stripe/organizations/{$this->organization()}/create-stripe-subscription-checkout/",
            ['price' => $price],
        )->json('url', '');
    }

    /**
     * A Stripe customer portal URL for managing payment details and invoices.
     */
    public function portal(): string
    {
        return (string) $this->connector->post("stripe/organizations/{$this->organization()}/create-billing-portal/")->json('url', '');
    }

    /**
     * Event usage for the current billing period, or an earlier one.
     */
    public function usage(int $periodsAgo = 0): BillingUsage
    {
        return BillingUsage::fromArray(self::object($this->connector->get(
            "stripe/subscriptions/{$this->organization()}/events_count/period/",
            ['periods_ago' => $periodsAgo],
        )));
    }

    /**
     * Event usage per day in the current billing period.
     *
     * @return list<DailyUsage>
     */
    public function dailyUsage(): array
    {
        /** @var list<array<string, mixed>> $days */
        $days = array_values(array_filter((array) $this->connector->get("stripe/subscriptions/{$this->organization()}/events_count/daily/")->json('data', []), is_array(...)));

        return array_map(DailyUsage::fromArray(...), $days);
    }

    public function overage(): Overage
    {
        return Overage::fromArray(self::object($this->connector->get("stripe/subscriptions/{$this->organization()}/overage/")));
    }

    /**
     * Turn metered overage billing on or off. Owner only.
     *
     * @param  int  $capCents  monthly spend cap
     */
    public function configureOverage(bool $enabled, int $capCents = 0): Overage
    {
        return Overage::fromArray(self::object($this->connector->post(
            "stripe/organizations/{$this->organization()}/overage/",
            ['enabled' => $enabled, 'capCents' => $capCents],
        )));
    }
}
