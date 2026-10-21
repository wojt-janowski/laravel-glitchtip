<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

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
}
