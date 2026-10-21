<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Event;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Events extends Resource
{
    /**
     * @return CursorPage<Event>
     */
    public function forIssue(int|string $issue, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->issuePath($issue), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Event::fromArray(...));
    }

    public function latest(int|string $issue): Event
    {
        return Event::fromArray(self::object($this->connector->get($this->issuePath($issue).'latest/')));
    }

    public function get(int|string $issue, string $event): Event
    {
        return Event::fromArray(self::object($this->connector->get($this->issuePath($issue).self::segment($event).'/')));
    }

    /**
     * @return CursorPage<Event>
     */
    public function forProject(string $project, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->projectPath($project), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Event::fromArray(...));
    }

    public function getForProject(string $project, string $event): Event
    {
        return Event::fromArray(self::object($this->connector->get($this->projectPath($project).self::segment($event).'/')));
    }

    /**
     * The event as originally ingested (Sentry event JSON): breadcrumbs,
     * exception values, request, extra and so on.
     *
     * @return array<string, mixed>
     */
    public function raw(int|string $issue, string $event): array
    {
        return self::object($this->connector->get(
            "organizations/{$this->organization()}/issues/".self::segment($issue).'/events/'.self::segment($event).'/json/',
        ));
    }

    private function issuePath(int|string $issue): string
    {
        return 'issues/'.self::segment($issue).'/events/';
    }

    private function projectPath(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/events/';
    }
}
