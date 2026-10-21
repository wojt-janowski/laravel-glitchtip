<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

/**
 * The definition of a project alert, used to create or replace one.
 *
 * An error alert fires when a project sees $quantity events within
 * $timespanMinutes; an uptime alert fires on monitor state changes.
 */
final readonly class AlertInput
{
    /**
     * @param  list<string>  $environments  empty for every environment
     * @param  list<array<string, mixed>>  $recipients  e.g. ['recipientType' => 'email'] or ['recipientType' => 'webhook', 'url' => '...']
     */
    public function __construct(
        public ?string $name = null,
        public ?int $timespanMinutes = null,
        public ?int $quantity = null,
        public bool $uptime = false,
        public array $environments = [],
        public array $recipients = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'timespanMinutes' => $this->timespanMinutes,
            'quantity' => $this->quantity,
            'uptime' => $this->uptime,
            'environments' => $this->environments,
            'alertRecipients' => $this->recipients,
        ];
    }
}
