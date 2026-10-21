<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A user's display preferences. Read from a user, and sent back whole when
 * updating one.
 */
final readonly class UserOptions
{
    /**
     * @param  string|null  $timezone  e.g. "Australia/Sydney"
     * @param  int|null  $stacktraceOrder  GlitchTip's frame order setting
     * @param  string|null  $preferredTheme  e.g. "light" or "dark"
     */
    public function __construct(
        public ?string $timezone = null,
        public ?int $stacktraceOrder = null,
        public ?string $language = null,
        public ?bool $clock24Hours = null,
        public ?string $preferredTheme = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            timezone: $a->nullableString('timezone'),
            stacktraceOrder: $a->nullableInt('stacktraceOrder'),
            language: $a->nullableString('language'),
            clock24Hours: $a->nullableBool('clock24Hours'),
            preferredTheme: $a->nullableString('preferredTheme'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timezone' => $this->timezone,
            'stacktraceOrder' => $this->stacktraceOrder,
            'language' => $this->language,
            'clock24Hours' => $this->clock24Hours,
            'preferredTheme' => $this->preferredTheme,
        ];
    }
}
