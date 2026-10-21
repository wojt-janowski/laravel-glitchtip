<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Project;
use WojtJanowski\GlitchTip\Data\ProjectInput;
use WojtJanowski\GlitchTip\Data\ProjectKey;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Projects extends Resource
{
    /**
     * Projects in the organization.
     *
     * @return CursorPage<Project>
     */
    public function list(?string $query = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("organizations/{$this->organization()}/projects/", [
            'query' => $query,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Project::fromArray(...));
    }

    /**
     * Projects across every organization the token can see.
     *
     * @return CursorPage<Project>
     */
    public function accessible(?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page('projects/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Project::fromArray(...));
    }

    /**
     * @return CursorPage<Project>
     */
    public function forTeam(string $team, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("teams/{$this->organization()}/".self::segment($team).'/projects/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Project::fromArray(...));
    }

    public function get(string $project): Project
    {
        return Project::fromArray(self::object($this->connector->get($this->path($project))));
    }

    /**
     * Client keys (DSNs) for a project.
     *
     * @return CursorPage<ProjectKey>
     */
    public function keys(string $project, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path($project).'keys/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], ProjectKey::fromArray(...));
    }

    /**
     * Create a project owned by a team.
     */
    public function create(string $team, ProjectInput $input): Project
    {
        return Project::fromArray(self::object(
            $this->connector->post("teams/{$this->organization()}/".self::segment($team).'/projects/', $input->toArray()),
        ));
    }

    public function update(string $project, ProjectInput $input): Project
    {
        return Project::fromArray(self::object($this->connector->put($this->path($project), $input->toArray())));
    }

    /**
     * Delete a project and all its events.
     */
    public function delete(string $project): void
    {
        $this->connector->delete($this->path($project));
    }

    public function key(string $project, string $key): ProjectKey
    {
        return ProjectKey::fromArray(self::object($this->connector->get($this->keyPath($project, $key))));
    }

    /**
     * Create a client key (DSN).
     *
     * @param  int|null  $rateLimitWindow  seconds; pass with $rateLimitCount
     */
    public function createKey(string $project, ?string $name = null, ?int $rateLimitWindow = null, ?int $rateLimitCount = null): ProjectKey
    {
        return ProjectKey::fromArray(self::object($this->connector->post(
            $this->path($project).'keys/',
            $this->keyBody($name, $rateLimitWindow, $rateLimitCount),
        )));
    }

    /**
     * GlitchTip 6.2 accepts this request but does not save the changes; it
     * returns the key as it was.
     */
    public function updateKey(string $project, string $key, ?string $name = null, ?int $rateLimitWindow = null, ?int $rateLimitCount = null): ProjectKey
    {
        return ProjectKey::fromArray(self::object($this->connector->put(
            $this->keyPath($project, $key),
            $this->keyBody($name, $rateLimitWindow, $rateLimitCount),
        )));
    }

    /**
     * Delete a client key. SDKs using its DSN stop being able to send events.
     */
    public function deleteKey(string $project, string $key): void
    {
        $this->connector->delete($this->keyPath($project, $key));
    }

    /**
     * Projects the user gets alert emails for: project id => -1 (default), 0 (off) or 1 (on).
     *
     * @return array<int, int>
     */
    public function alertPreferences(int|string $user = 'me'): array
    {
        $preferences = [];

        foreach (self::object($this->connector->get('users/'.self::segment($user).'/notifications/alerts/')) as $project => $status) {
            $preferences[(int) $project] = is_numeric($status) ? (int) $status : -1;
        }

        return $preferences;
    }

    /**
     * @param  array<int, int>  $preferences  project id => -1 (default), 0 (off) or 1 (on)
     */
    public function updateAlertPreferences(array $preferences, int|string $user = 'me'): void
    {
        $body = [];

        foreach ($preferences as $project => $status) {
            $body[(string) $project] = $status;
        }

        $this->connector->put('users/'.self::segment($user).'/notifications/alerts/', $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function keyBody(?string $name, ?int $rateLimitWindow, ?int $rateLimitCount): array
    {
        $body = $name === null ? [] : ['name' => $name];

        if ($rateLimitWindow !== null && $rateLimitCount !== null) {
            $body['rateLimit'] = ['window' => $rateLimitWindow, 'count' => $rateLimitCount];
        }

        return $body;
    }

    private function keyPath(string $project, string $key): string
    {
        return $this->path($project).'keys/'.self::segment($key).'/';
    }

    private function path(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/';
    }
}
