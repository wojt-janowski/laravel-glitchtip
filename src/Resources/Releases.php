<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use DateTimeInterface;
use WojtJanowski\GlitchTip\Data\Deploy;
use WojtJanowski\GlitchTip\Data\Release;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Releases extends Resource
{
    /**
     * Releases in the organization, or in one project when a slug is given.
     *
     * @return CursorPage<Release>
     */
    public function list(?string $project = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        $path = $project === null
            ? "organizations/{$this->organization()}/releases/"
            : "projects/{$this->organization()}/".self::segment($project).'/releases/';

        return $this->page($path, [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Release::fromArray(...));
    }

    public function get(string $version): Release
    {
        return Release::fromArray(self::object($this->connector->get($this->path($version))));
    }

    /**
     * @param  list<string>  $projects  project slugs the release belongs to
     * @param  string|null  $ref  commit SHA or tag
     */
    public function create(string $version, array $projects, ?string $ref = null, ?DateTimeInterface $dateReleased = null): Release
    {
        return Release::fromArray(self::object($this->connector->post("organizations/{$this->organization()}/releases/", array_filter([
            'version' => $version,
            'projects' => $projects,
            'ref' => $ref,
            'dateReleased' => $dateReleased?->format(DateTimeInterface::ATOM),
        ], fn (mixed $value): bool => $value !== null))));
    }

    /**
     * Change only the fields given; null leaves a field untouched.
     */
    public function update(string $version, ?string $ref = null, ?DateTimeInterface $dateReleased = null): Release
    {
        return Release::fromArray(self::object($this->connector->put($this->path($version), array_filter([
            'ref' => $ref,
            'dateReleased' => $dateReleased?->format(DateTimeInterface::ATOM),
        ], fn (mixed $value): bool => $value !== null))));
    }

    /**
     * Mark a release as released now (or at the given time).
     */
    public function finalize(string $version, ?DateTimeInterface $at = null): Release
    {
        return $this->update($version, dateReleased: $at ?? now());
    }

    public function delete(string $version): void
    {
        $this->connector->delete($this->path($version));
    }

    /**
     * @return list<Deploy>
     */
    public function deploys(string $version): array
    {
        return self::listOf($this->connector->get($this->path($version).'deploys/'), Deploy::fromArray(...));
    }

    public function createDeploy(
        string $version,
        string $environment,
        ?string $url = null,
        ?DateTimeInterface $dateStarted = null,
        ?DateTimeInterface $dateFinished = null,
    ): Deploy {
        return Deploy::fromArray(self::object($this->connector->post($this->path($version).'deploys/', array_filter([
            'environment' => $environment,
            'url' => $url,
            'dateStarted' => $dateStarted?->format(DateTimeInterface::ATOM),
            'dateFinished' => $dateFinished?->format(DateTimeInterface::ATOM),
        ], fn (mixed $value): bool => $value !== null))));
    }

    private function path(string $version): string
    {
        return "organizations/{$this->organization()}/releases/".self::segment($version).'/';
    }
}
