<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use DateTimeInterface;
use WojtJanowski\GlitchTip\Data\LogEntry;
use WojtJanowski\GlitchTip\Data\LogResource;
use WojtJanowski\GlitchTip\Data\LogStats;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Logs extends Resource
{
    /**
     * Log lines, newest first.
     *
     * @param  list<int>  $projects  project ids
     * @param  list<string>  $levels  e.g. ['error', 'warn']
     * @return CursorPage<LogEntry>
     */
    public function list(
        ?string $query = null,
        array $projects = [],
        array $levels = [],
        ?string $service = null,
        ?string $environment = null,
        ?string $host = null,
        ?string $traceId = null,
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): CursorPage {
        return $this->page($this->path(), [
            'query' => $query,
            'project' => $projects,
            'level' => $levels,
            'service' => $service,
            'environment' => $environment,
            'host' => $host,
            'traceId' => $traceId,
            'start' => $start,
            'end' => $end,
            'limit' => $limit,
            'cursor' => $cursor,
        ], LogEntry::fromArray(...));
    }

    public function get(string $log): LogEntry
    {
        return LogEntry::fromArray(self::object($this->connector->get($this->path().self::segment($log).'/')));
    }

    /**
     * Log volume over time, one series per level.
     *
     * @param  list<int>  $projects
     * @param  list<string>  $levels
     * @param  list<string>  $services
     * @param  list<string>  $environments
     */
    public function stats(
        array $projects = [],
        array $levels = [],
        array $services = [],
        array $environments = [],
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
    ): LogStats {
        return LogStats::fromArray(self::object($this->connector->get($this->path().'stats/', [
            'project' => $projects,
            'level' => $levels,
            'service' => $services,
            'environment' => $environments,
            'start' => $start,
            'end' => $end,
        ])));
    }

    /**
     * Known services, environments and hosts, most recently seen first.
     *
     * @param  string|null  $type  "service", "environment" or "host"; all when omitted
     * @return list<LogResource>
     */
    public function resources(?string $type = null): array
    {
        return self::listOf($this->connector->get($this->path().'resources/', ['resource_type' => $type]), LogResource::fromArray(...));
    }

    private function path(): string
    {
        return "organizations/{$this->organization()}/logs/";
    }
}
