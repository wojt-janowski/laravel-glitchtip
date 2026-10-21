<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use DateTimeInterface;
use InvalidArgumentException;
use WojtJanowski\GlitchTip\Data\Commit;
use WojtJanowski\GlitchTip\Data\Issue;
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueHash;
use WojtJanowski\GlitchTip\Data\IssueStats;
use WojtJanowski\GlitchTip\Data\IssueTag;
use WojtJanowski\GlitchTip\Data\IssueUpdate;
use WojtJanowski\GlitchTip\Data\UserReport;
use WojtJanowski\GlitchTip\Enums\IssueSort;
use WojtJanowski\GlitchTip\Enums\IssueStatsPeriod;
use WojtJanowski\GlitchTip\Enums\IssueStatus;
use WojtJanowski\GlitchTip\Exceptions\RequestFailedException;
use WojtJanowski\GlitchTip\Exceptions\UnsupportedByServerException;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Issues extends Resource
{
    private const string SHORT_ID_VERSION = '7.0.0';

    /**
     * Issues across the organization.
     *
     * @param  string|null  $query  GlitchTip search syntax, e.g. "is:unresolved level:error"
     * @param  list<int>  $projects  project ids
     * @param  list<string>  $environments
     * @return CursorPage<Issue>
     */
    public function list(
        ?string $query = null,
        array $projects = [],
        array $environments = [],
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?IssueSort $sort = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): CursorPage {
        return $this->page("organizations/{$this->organization()}/issues/", [
            'query' => $query,
            'project' => $projects,
            'environment' => $environments,
            'start' => $start,
            'end' => $end,
            'sort' => $sort,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Issue::fromArray(...));
    }

    /**
     * Issues in one project, by slug.
     *
     * @param  list<string>  $environments
     * @return CursorPage<Issue>
     */
    public function forProject(
        string $project,
        ?string $query = null,
        array $environments = [],
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?IssueSort $sort = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): CursorPage {
        return $this->page("projects/{$this->organization()}/".self::segment($project).'/issues/', [
            'query' => $query,
            'environment' => $environments,
            'start' => $start,
            'end' => $end,
            'sort' => $sort,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Issue::fromArray(...));
    }

    public function get(int|string $issue): Issue
    {
        return Issue::fromArray(self::object($this->connector->get($this->path($issue))));
    }

    /**
     * Look up an issue by its short id, e.g. "BACKEND-1A".
     *
     * Needs GlitchTip 7. Older servers answer 404 for every short id, so a 404
     * is checked against the server version to tell the two cases apart.
     *
     * @throws UnsupportedByServerException when the server predates the endpoint
     */
    public function findByShortId(string $shortId): Issue
    {
        try {
            $response = $this->connector->get("organizations/{$this->organization()}/shortids/".self::segment($shortId).'/');
        } catch (RequestFailedException $exception) {
            $server = new Instance($this->connector)->settings();

            if ($exception->isNotFound() && ! $server->versionAtLeast(self::SHORT_ID_VERSION)) {
                throw new UnsupportedByServerException('Short id lookup', self::SHORT_ID_VERSION, $server->version);
            }

            throw $exception;
        }

        /** @var array<string, mixed> $group */
        $group = $response->json('group', []);

        return Issue::fromArray($group);
    }

    public function update(int|string $issue, IssueUpdate $update): Issue
    {
        return Issue::fromArray(self::object($this->connector->put($this->path($issue), $update->toArray())));
    }

    public function resolve(int|string $issue): Issue
    {
        return $this->update($issue, IssueUpdate::resolved());
    }

    public function unresolve(int|string $issue): Issue
    {
        return $this->update($issue, IssueUpdate::unresolved());
    }

    public function ignore(int|string $issue): Issue
    {
        return $this->update($issue, IssueUpdate::ignored());
    }

    /**
     * @param  string|null  $assignee  "user:<id>", "team:<slug>", an email, or null to unassign
     */
    public function assign(int|string $issue, ?string $assignee): Issue
    {
        return $this->update($issue, IssueUpdate::assignedTo($assignee));
    }

    public function delete(int|string $issue): void
    {
        $this->connector->delete($this->path($issue));
    }

    /**
     * Apply one update to every issue the filter matches. GlitchTip updates
     * the first 50 immediately and queues the rest.
     */
    public function bulkUpdate(IssueFilter $filter, IssueUpdate $update): void
    {
        $this->connector->put("organizations/{$this->organization()}/issues/", $update->toArray(), $filter->toQuery());
    }

    /**
     * Delete every issue the filter matches.
     */
    public function bulkDelete(IssueFilter $filter): void
    {
        $this->connector->delete("organizations/{$this->organization()}/issues/", $filter->toQuery());
    }

    /**
     * Tag distributions for an issue, optionally for a single tag key.
     *
     * @return list<IssueTag>
     */
    public function tags(int|string $issue, ?string $key = null): array
    {
        return self::listOf($this->connector->get($this->path($issue).'tags/', ['key' => $key]), IssueTag::fromArray(...));
    }

    /**
     * @return list<Commit>
     */
    public function commits(int|string $issue): array
    {
        return self::listOf($this->connector->get($this->path($issue).'commits/'), Commit::fromArray(...));
    }

    /**
     * @return CursorPage<UserReport>
     */
    public function userReports(int|string $issue, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path($issue).'user-reports/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], UserReport::fromArray(...));
    }

    /**
     * Bulk update one project's issues. Without ids GlitchTip only allows a
     * status change, applied to issues in $scope (unresolved by default).
     */
    public function bulkUpdateForProject(string $project, IssueFilter $filter, IssueUpdate $update, ?IssueStatus $scope = null): void
    {
        $this->connector->put(
            "projects/{$this->organization()}/".self::segment($project).'/issues/',
            $update->toArray(),
            $filter->toQuery() + ['status' => $scope],
        );
    }

    /**
     * Event counts over time for several issues at once.
     *
     * @param  list<int|string>  $issues
     * @return list<IssueStats>
     */
    public function stats(array $issues, IssueStatsPeriod $period = IssueStatsPeriod::Day): array
    {
        return self::listOf($this->connector->get("organizations/{$this->organization()}/issues-stats/", [
            'groups' => $issues,
            'statsPeriod' => $period,
        ]), IssueStats::fromArray(...));
    }

    /**
     * The fingerprint hashes grouped into an issue.
     *
     * @return CursorPage<IssueHash>
     */
    public function hashes(int|string $issue, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->hashPath($issue), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], IssueHash::fromArray(...));
    }

    /**
     * Split hashes out of an issue (unmerge). Future events with those
     * hashes start new issues; processing happens in the background.
     *
     * @param  list<string>  $hashes
     */
    public function deleteHashes(int|string $issue, array $hashes): void
    {
        if ($hashes === []) {
            throw new InvalidArgumentException('Pass at least one hash id to delete.');
        }

        $this->connector->delete($this->hashPath($issue), ['id' => $hashes]);
    }

    private function hashPath(int|string $issue): string
    {
        return "organizations/{$this->organization()}/issues/".self::segment($issue).'/hashes/';
    }

    private function path(int|string $issue): string
    {
        return 'issues/'.self::segment($issue).'/';
    }
}
