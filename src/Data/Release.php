<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Release
{
    /**
     * @param  list<string>  $projects  project slugs (or names, where a project has no slug)
     */
    public function __construct(
        public string $version,
        public string $shortVersion,
        public CarbonImmutable $dateCreated,
        public ?CarbonImmutable $dateReleased = null,
        public ?string $ref = null,
        public ?string $url = null,
        public array $projects = [],
        public int $commitCount = 0,
        public int $deployCount = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        $projects = array_map(
            function (array $project): string {
                $attributes = Attributes::of($project);

                return $attributes->nullableString('slug') ?? $attributes->string('name');
            },
            $a->objects('projects'),
        );

        return new self(
            version: $a->string('version'),
            shortVersion: $a->string('shortVersion'),
            dateCreated: $a->date('dateCreated'),
            dateReleased: $a->nullableDate('dateReleased'),
            ref: $a->nullableString('ref'),
            url: $a->nullableString('url'),
            projects: $projects,
            commitCount: $a->int('commitCount'),
            deployCount: $a->int('deployCount'),
        );
    }
}
