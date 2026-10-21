<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Alert;
use WojtJanowski\GlitchTip\Data\AlertInput;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Alerts extends Resource
{
    /**
     * @return CursorPage<Alert>
     */
    public function list(string $project, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path($project), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Alert::fromArray(...));
    }

    public function create(string $project, AlertInput $input): Alert
    {
        return Alert::fromArray(self::object($this->connector->post($this->path($project), $input->toArray())));
    }

    /**
     * Replace an alert's definition.
     */
    public function update(string $project, int $alert, AlertInput $input): Alert
    {
        return Alert::fromArray(self::object($this->connector->put($this->path($project).$alert.'/', $input->toArray())));
    }

    public function delete(string $project, int $alert): void
    {
        $this->connector->delete($this->path($project).$alert.'/');
    }

    private function path(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/alerts/';
    }
}
