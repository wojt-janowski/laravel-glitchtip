<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Enums\IssueStatus;
use WojtJanowski\GlitchTip\Enums\IssueType;
use WojtJanowski\GlitchTip\Enums\Level;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Issue
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $statusDetails
     */
    public function __construct(
        public string $id,
        public string $shortId,
        public string $title,
        public ?string $culprit,
        public ?IssueType $type,
        public ?Level $level,
        public ?IssueStatus $status,
        public int $count,
        public ProjectReference $project,
        public CarbonImmutable $firstSeen,
        public CarbonImmutable $lastSeen,
        public int $userCount = 0,
        public int $numComments = 0,
        public ?string $permalink = null,
        public ?IssueActor $assignedTo = null,
        public ?IssueRelease $firstRelease = null,
        public ?IssueRelease $lastRelease = null,
        public array $metadata = [],
        public array $statusDetails = [],
        public ?int $userReportCount = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);
        $assignedTo = $a->nullableObject('assignedTo');
        $firstRelease = $a->nullableObject('firstRelease');
        $lastRelease = $a->nullableObject('lastRelease');

        /** @var array<string, mixed> $metadata */
        $metadata = $a->array('metadata');

        /** @var array<string, mixed> $statusDetails */
        $statusDetails = $a->array('statusDetails');

        return new self(
            id: $a->string('id'),
            shortId: $a->string('shortId'),
            title: $a->string('title'),
            culprit: $a->nullableString('culprit'),
            type: $a->enum('type', IssueType::class),
            level: $a->enum('level', Level::class),
            status: $a->enum('status', IssueStatus::class),
            count: $a->int('count'),
            project: ProjectReference::fromArray($a->nullableObject('project') ?? []),
            firstSeen: $a->date('firstSeen'),
            lastSeen: $a->date('lastSeen'),
            userCount: $a->int('userCount'),
            numComments: $a->int('numComments'),
            permalink: $a->nullableString('permalink'),
            assignedTo: $assignedTo === null ? null : IssueActor::fromArray($assignedTo),
            firstRelease: $firstRelease === null ? null : IssueRelease::fromArray($firstRelease),
            lastRelease: $lastRelease === null ? null : IssueRelease::fromArray($lastRelease),
            metadata: $metadata,
            statusDetails: $statusDetails,
            userReportCount: $a->nullableInt('userReportCount'),
        );
    }

    public function isResolved(): bool
    {
        return $this->status === IssueStatus::Resolved;
    }
}
