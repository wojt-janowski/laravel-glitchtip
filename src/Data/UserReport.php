<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * Feedback a user submitted through the crash-report dialog.
 */
final readonly class UserReport
{
    public function __construct(
        public ?int $id,
        public string $eventId,
        public string $name,
        public string $email,
        public string $comments,
        public CarbonImmutable $dateCreated,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->nullableInt('id'),
            eventId: $a->string('eventID'),
            name: $a->string('name'),
            email: $a->string('email'),
            comments: $a->string('comments'),
            dateCreated: $a->date('dateCreated'),
        );
    }
}
