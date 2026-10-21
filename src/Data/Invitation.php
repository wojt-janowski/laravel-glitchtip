<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * An organization invitation, as seen by the invitee.
 */
final readonly class Invitation
{
    public function __construct(
        public bool $accepted,
        public Member $member,
        public ?Organization $organization = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);
        $member = Attributes::of($a->nullableObject('orgUser') ?? []);

        return new self(
            accepted: $a->bool('acceptInvite'),
            member: Member::fromArray($a->nullableObject('orgUser') ?? []),
            organization: $member->map('organization', Organization::fromArray(...)),
        );
    }
}
