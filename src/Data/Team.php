<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Team
{
    /**
     * @param  list<Project>  $projects  present on organization team listings
     */
    public function __construct(
        public string $id,
        public string $slug,
        public CarbonImmutable $dateCreated,
        public bool $isMember = false,
        public int $memberCount = 0,
        public array $projects = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            id: $a->string('id'),
            slug: $a->string('slug'),
            dateCreated: $a->date('dateCreated'),
            isMember: $a->bool('isMember'),
            memberCount: $a->int('memberCount'),
            projects: array_map(Project::fromArray(...), $a->objects('projects')),
        );
    }
}
