<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * One fingerprint hash grouped into an issue. Deleting hashes splits them out of the issue.
 */
final readonly class IssueHash
{
    public function __construct(
        public string $id,
        public ?Event $latestEvent = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            latestEvent: $a->map('latestEvent', Event::fromArray(...)),
        );
    }
}
