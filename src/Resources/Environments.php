<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Environment;
use WojtJanowski\GlitchTip\Data\ProjectEnvironment;
use WojtJanowski\GlitchTip\Enums\EnvironmentVisibility;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Environments extends Resource
{
    /**
     * @return CursorPage<Environment>
     */
    public function list(?EnvironmentVisibility $visibility = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("organizations/{$this->organization()}/environments/", [
            'visibility' => $visibility,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Environment::fromArray(...));
    }

    /**
     * Environments as configured for one project, including whether each is hidden.
     *
     * @return CursorPage<ProjectEnvironment>
     */
    public function forProject(string $project, ?EnvironmentVisibility $visibility = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->projectPath($project), [
            'visibility' => $visibility,
            'limit' => $limit,
            'cursor' => $cursor,
        ], ProjectEnvironment::fromArray(...));
    }

    /**
     * Hide or show an environment in a project.
     */
    public function update(string $project, string $environment, bool $hidden): ProjectEnvironment
    {
        return ProjectEnvironment::fromArray(self::object($this->connector->put(
            $this->projectPath($project).self::segment($environment).'/',
            ['name' => $environment, 'isHidden' => $hidden],
        )));
    }

    public function hide(string $project, string $environment): ProjectEnvironment
    {
        return $this->update($project, $environment, hidden: true);
    }

    public function show(string $project, string $environment): ProjectEnvironment
    {
        return $this->update($project, $environment, hidden: false);
    }

    private function projectPath(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/environments/';
    }
}
