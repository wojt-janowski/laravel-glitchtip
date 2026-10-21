<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use DateTimeInterface;
use WojtJanowski\GlitchTip\Data\AssembleStatus;
use WojtJanowski\GlitchTip\Data\ChunkedFile;
use WojtJanowski\GlitchTip\Data\Commit;
use WojtJanowski\GlitchTip\Data\Deploy;
use WojtJanowski\GlitchTip\Data\Release;
use WojtJanowski\GlitchTip\Data\ReleaseFile;
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

    /**
     * @param  string|null  $project  look the release up within one project
     */
    public function get(string $version, ?string $project = null): Release
    {
        return Release::fromArray(self::object($this->connector->get($this->path($version, $project))));
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
     *
     * @param  string|null  $project  update the release within one project
     */
    public function update(string $version, ?string $ref = null, ?DateTimeInterface $dateReleased = null, ?string $project = null): Release
    {
        return Release::fromArray(self::object($this->connector->put($this->path($version, $project), array_filter([
            'ref' => $ref,
            'dateReleased' => $dateReleased?->format(DateTimeInterface::ATOM),
        ], fn (mixed $value): bool => $value !== null))));
    }

    /**
     * Mark a release as released now (or at the given time).
     */
    public function finalize(string $version, ?DateTimeInterface $at = null, ?string $project = null): Release
    {
        return $this->update($version, dateReleased: $at ?? now(), project: $project);
    }

    /**
     * @param  string|null  $project  delete the release from one project only
     */
    public function delete(string $version, ?string $project = null): void
    {
        $this->connector->delete($this->path($version, $project));
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

    private function path(string $version, ?string $project = null): string
    {
        return $project === null
            ? "organizations/{$this->organization()}/releases/".self::segment($version).'/'
            : $this->projectPath($project).self::segment($version).'/';
    }

    /**
     * Create a release in one project.
     *
     * @param  string|null  $ref  commit SHA or tag
     */
    public function createForProject(string $project, string $version, ?string $ref = null, ?DateTimeInterface $dateReleased = null): Release
    {
        return Release::fromArray(self::object($this->connector->post($this->projectPath($project), array_filter([
            'version' => $version,
            'ref' => $ref,
            'dateReleased' => $dateReleased?->format(DateTimeInterface::ATOM),
        ], fn (mixed $value): bool => $value !== null))));
    }

    /**
     * Artifacts (source maps, bundles) attached to a release.
     *
     * @return CursorPage<ReleaseFile>
     */
    public function files(string $version, ?string $project = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path($version, $project).'files/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], ReleaseFile::fromArray(...));
    }

    public function file(string $version, int $file, string $project): ReleaseFile
    {
        return ReleaseFile::fromArray(self::object($this->connector->get($this->path($version, $project)."files/{$file}/")));
    }

    public function deleteFile(string $version, int $file, ?string $project = null): void
    {
        $this->connector->delete($this->path($version, $project)."files/{$file}/");
    }

    /**
     * @return list<Commit>
     */
    public function commits(string $version): array
    {
        return self::listOf($this->connector->get($this->path($version).'commits/'), Commit::fromArray(...));
    }

    /**
     * Replace a release's commits (GlitchTip keeps the first 1000).
     *
     * @param  list<Commit>  $commits
     */
    public function setCommits(string $version, array $commits): Release
    {
        return Release::fromArray(self::object($this->connector->post(
            $this->path($version).'commits/',
            array_map(fn (Commit $commit): array => $commit->toArray(), $commits),
        )));
    }

    /**
     * Assemble an uploaded file into the release's artifacts. Upload it with
     * uploads()->upload() first.
     */
    public function assemble(string $version, ChunkedFile $file): AssembleStatus
    {
        return AssembleStatus::fromArray(self::object($this->connector->post(
            $this->path($version).'assemble/',
            ['checksum' => $file->checksum, 'chunks' => $file->chunks],
        )));
    }

    private function projectPath(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/releases/';
    }
}
