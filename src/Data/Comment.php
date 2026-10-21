<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Comment
{
    public function __construct(
        public ?int $id,
        public string $text,
        public CarbonImmutable $dateCreated,
        public ?string $userId = null,
        public ?string $userEmail = null,
        public ?string $type = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);
        $body = Attributes::of($a->nullableObject('data') ?? []);
        $user = Attributes::of($a->nullableObject('user') ?? []);

        return new self(
            id: $a->nullableInt('id'),
            text: $body->string('text'),
            dateCreated: $a->date('dateCreated'),
            userId: $user->nullableString('id'),
            userEmail: $user->nullableString('email'),
            type: $a->nullableString('type'),
        );
    }
}
