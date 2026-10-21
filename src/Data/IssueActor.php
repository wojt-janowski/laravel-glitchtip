<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * The user or team an issue is assigned to.
 */
final readonly class IssueActor
{
    public function __construct(
        public string $type,
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $username = null,
        public ?string $slug = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            type: $a->string('type'),
            id: $a->string('id'),
            name: $a->string('name'),
            email: $a->nullableString('email'),
            username: $a->nullableString('username'),
            slug: $a->nullableString('slug'),
        );
    }

    public function isTeam(): bool
    {
        return $this->type === 'team';
    }
}
