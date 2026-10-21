<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Selects the issues a bulk update or delete applies to.
 *
 * GlitchTip applies a bulk call with no filter to every issue in the
 * organization, so an empty filter is refused unless built with everything().
 */
final readonly class IssueFilter
{
    /**
     * @param  list<int|string>  $ids
     * @param  list<int>  $projects  project ids
     * @param  list<string>  $environments
     */
    public function __construct(
        public array $ids = [],
        public ?string $query = null,
        public array $projects = [],
        public array $environments = [],
        public ?DateTimeInterface $start = null,
        public ?DateTimeInterface $end = null,
        bool $everything = false,
    ) {
        if (! $everything && $this->isEmpty()) {
            throw new InvalidArgumentException('An issue filter needs at least one criterion. Use IssueFilter::everything() to target every issue deliberately.');
        }
    }

    /**
     * @param  list<int|string>  $ids
     */
    public static function ids(array $ids): self
    {
        return new self(ids: $ids);
    }

    /**
     * Every issue in the organization (or project, for project-scoped calls).
     */
    public static function everything(): self
    {
        return new self(everything: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return [
            'id' => $this->ids,
            'query' => $this->query,
            'project' => $this->projects,
            'environment' => $this->environments,
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    private function isEmpty(): bool
    {
        return $this->ids === []
            && ($this->query === null || $this->query === '')
            && $this->projects === []
            && $this->environments === []
            && ! $this->start instanceof DateTimeInterface
            && ! $this->end instanceof DateTimeInterface;
    }
}
