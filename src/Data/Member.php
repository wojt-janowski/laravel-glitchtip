<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Enums\MemberRole;
use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * A user, or a pending invitation, in an organization.
 */
final readonly class Member
{
    /**
     * @param  bool  $pending  true until the invitation is accepted
     * @param  list<string>  $teams  team slugs, present on single-member responses
     * @param  ?string  $inviteLink  present when an invitation was just created
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $roleName,
        public CarbonImmutable $dateCreated,
        public bool $pending,
        public bool $isOwner,
        public ?MemberRole $role = null,
        public ?User $user = null,
        public array $teams = [],
        public ?string $inviteLink = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            email: $a->string('email'),
            roleName: $a->string('roleName'),
            dateCreated: $a->date('dateCreated'),
            pending: $a->bool('pending'),
            isOwner: $a->bool('isOwner'),
            role: $a->enum('role', MemberRole::class),
            user: $a->map('user', User::fromArray(...)),
            teams: $a->strings('teams'),
            inviteLink: $a->nullableString('inviteLink'),
        );
    }
}
