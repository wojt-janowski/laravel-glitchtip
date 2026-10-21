<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Monitor;
use WojtJanowski\GlitchTip\Data\MonitorCheck;
use WojtJanowski\GlitchTip\Data\MonitorInput;
use WojtJanowski\GlitchTip\Data\StatusPage;
use WojtJanowski\GlitchTip\Enums\MonitorSort;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Monitors extends Resource
{
    /**
     * @return CursorPage<Monitor>
     */
    public function list(?MonitorSort $sort = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path(), [
            'sort' => $sort,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Monitor::fromArray(...));
    }

    public function get(int $monitor): Monitor
    {
        return Monitor::fromArray(self::object($this->connector->get($this->path($monitor))));
    }

    public function create(MonitorInput $input): Monitor
    {
        return Monitor::fromArray(self::object($this->connector->post($this->path(), $input->toArray())));
    }

    /**
     * Replace a monitor's definition.
     */
    public function update(int $monitor, MonitorInput $input): Monitor
    {
        return Monitor::fromArray(self::object($this->connector->put($this->path($monitor), $input->toArray())));
    }

    public function delete(int $monitor): void
    {
        $this->connector->delete($this->path($monitor));
    }

    /**
     * Check history, newest first.
     *
     * @param  bool|null  $onlyChanges  true for only the checks where the up/down state flipped
     * @return CursorPage<MonitorCheck>
     */
    public function checks(int $monitor, ?bool $onlyChanges = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path($monitor).'checks/', [
            'is_change' => $onlyChanges,
            'limit' => $limit,
            'cursor' => $cursor,
        ], MonitorCheck::fromArray(...));
    }

    /**
     * Report a heartbeat monitor as up. Needs no token, so it can be called
     * from cron jobs and workers with only the endpoint id.
     */
    public function heartbeat(string $endpoint): MonitorCheck
    {
        return MonitorCheck::fromArray(self::object(
            $this->connector->postPublic("organizations/{$this->organization()}/heartbeat_check/".self::segment($endpoint).'/'),
        ));
    }

    /**
     * @return CursorPage<StatusPage>
     */
    public function statusPages(?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("organizations/{$this->organization()}/status-pages/", [
            'limit' => $limit,
            'cursor' => $cursor,
        ], StatusPage::fromArray(...));
    }

    public function createStatusPage(string $name, bool $public = false): StatusPage
    {
        return StatusPage::fromArray(self::object($this->connector->post(
            "organizations/{$this->organization()}/status-pages/",
            ['name' => $name, 'isPublic' => $public],
        )));
    }

    private function path(?int $monitor = null): string
    {
        $path = "organizations/{$this->organization()}/monitors/";

        return $monitor === null ? $path : $path.$monitor.'/';
    }
}
