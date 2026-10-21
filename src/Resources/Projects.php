<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Project;
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

    private function path(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/';
    }
}
