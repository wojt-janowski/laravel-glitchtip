<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Project
{
    /**
     * @param  list<TeamReference>  $teams  present on organization project listings
     * @param  Organization|null  $organization  present on single-project and /projects/ responses
     */
    public function __construct(
        public string $id,
        public ?string $slug,
        public string $name,
        public ?string $platform,
        public CarbonImmutable $dateCreated,
        public ?CarbonImmutable $firstEvent = null,
        public bool $isMember = false,
        public bool $isBookmarked = false,
        public bool $isPublic = false,
        public ?string $color = null,
        public array $teams = [],
        public ?Organization $organization = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);
        $organization = $a->nullableObject('organization');

        return new self(
            id: $a->string('id'),
            slug: $a->nullableString('slug'),
            name: $a->string('name'),
            platform: $a->nullableString('platform'),
            dateCreated: $a->date('dateCreated'),
            firstEvent: $a->nullableDate('firstEvent'),
            isMember: $a->bool('isMember'),
            isBookmarked: $a->bool('isBookmarked'),
            isPublic: $a->bool('isPublic'),
            color: $a->nullableString('color'),
            teams: array_map(TeamReference::fromArray(...), $a->objects('teams')),
            organization: $organization === null ? null : Organization::fromArray($organization),
        );
    }
}
