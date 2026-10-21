<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Project;
use WojtJanowski\GlitchTip\Data\Team;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Teams extends Resource
{
    /**
     * @return CursorPage<Team>
     */
    public function list(?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("organizations/{$this->organization()}/teams/", [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Team::fromArray(...));
    }

    public function get(string $team): Team
    {
        return Team::fromArray(self::object(
            $this->connector->get("teams/{$this->organization()}/".self::segment($team).'/'),
        ));
    }

    /**
     * @return CursorPage<Team>
     */
    public function forProject(string $project, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("projects/{$this->organization()}/".self::segment($project).'/teams/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Team::fromArray(...));
    }

    public function create(string $slug): Team
    {
        return Team::fromArray(self::object($this->connector->post("organizations/{$this->organization()}/teams/", ['slug' => $slug])));
    }

    /**
     * Rename a team's slug.
     */
    public function update(string $team, string $slug): Team
    {
        return Team::fromArray(self::object($this->connector->put($this->path($team), ['slug' => $slug])));
    }

    public function delete(string $team): void
    {
        $this->connector->delete($this->path($team));
    }

    /**
     * @param  int|string  $member  organization member id, or "me"
     */
    public function addMember(string $team, int|string $member): Team
    {
        return Team::fromArray(self::object($this->connector->post($this->memberPath($team, $member))));
    }

    /**
     * @param  int|string  $member  organization member id, or "me"
     */
    public function removeMember(string $team, int|string $member): Team
    {
        return Team::fromArray(self::object($this->connector->delete($this->memberPath($team, $member))));
    }

    /**
     * Give a team access to a project.
     */
    public function addToProject(string $team, string $project): Project
    {
        return Project::fromArray(self::object($this->connector->post($this->projectPath($team, $project))));
    }

    public function removeFromProject(string $team, string $project): Project
    {
        return Project::fromArray(self::object($this->connector->delete($this->projectPath($team, $project))));
    }

    private function path(string $team): string
    {
        return "teams/{$this->organization()}/".self::segment($team).'/';
    }

    private function memberPath(string $team, int|string $member): string
    {
        return "organizations/{$this->organization()}/members/".self::segment($member).'/teams/'.self::segment($team).'/';
    }

    private function projectPath(string $team, string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/teams/'.self::segment($team).'/';
    }
}
