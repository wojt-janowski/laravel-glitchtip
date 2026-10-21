<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use RuntimeException;
use WojtJanowski\GlitchTip\Data\AssembleStatus;
use WojtJanowski\GlitchTip\Data\ChunkedFile;
use WojtJanowski\GlitchTip\Data\ChunkUploadOptions;
use WojtJanowski\GlitchTip\Data\DebugFile;
use WojtJanowski\GlitchTip\Support\CursorPage;

/**
 * File uploads the way sentry-cli does them: upload() sends a file in
 * gzipped chunks, then an assemble call turns the chunks into a source map
 * bundle, release artifact or debug file.
 *
 *     $file = GlitchTip::uploads()->upload($bundleZip);
 *     GlitchTip::uploads()->assembleArtifactBundle($file, projects: ['backend'], version: '1.4.0');
 */
final readonly class Uploads extends Resource
{
    public function options(): ChunkUploadOptions
    {
        return ChunkUploadOptions::fromArray(self::object($this->connector->get($this->chunkPath())));
    }

    /**
     * Upload a file's contents in chunks sized to the server's limits.
     */
    public function upload(string $contents): ChunkedFile
    {
        $options = $this->options();
        $chunkSize = max(1, $options->chunkSize);
        $perRequest = max(1, $options->chunksPerRequest);

        $chunks = $contents === '' ? [''] : str_split($contents, $chunkSize);
        $checksums = [];
        $batch = [];

        foreach ($chunks as $chunk) {
            $checksum = sha1($chunk);
            $checksums[] = $checksum;
            $batch[] = ['contents' => $this->gzip($chunk), 'filename' => $checksum];

            if (count($batch) === $perRequest) {
                $this->connector->postFiles($this->chunkPath(), 'file_gzip', $batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->connector->postFiles($this->chunkPath(), 'file_gzip', $batch);
        }

        return new ChunkedFile(checksum: sha1($contents), chunks: $checksums, size: strlen($contents));
    }

    /**
     * Assemble an uploaded artifact bundle (source maps with debug ids).
     *
     * @param  list<string>  $projects  project slugs
     */
    public function assembleArtifactBundle(ChunkedFile $file, array $projects, ?string $version = null): AssembleStatus
    {
        return AssembleStatus::fromArray(self::object($this->connector->post(
            "organizations/{$this->organization()}/artifactbundle/assemble/",
            ['checksum' => $file->checksum, 'chunks' => $file->chunks, 'projects' => $projects, 'version' => $version],
        )));
    }

    /**
     * Assemble an uploaded debug information file (dSYM, ELF, PDB, ProGuard mapping).
     */
    public function assembleDebugFile(string $project, ChunkedFile $file, string $name, ?string $debugId = null): AssembleStatus
    {
        return $this->assembleDebugFiles($project, [$file->checksum => ['name' => $name, 'chunks' => $file->chunks, 'debugId' => $debugId]])[$file->checksum]
            ?? new AssembleStatus(state: 'error', detail: 'GlitchTip returned no status for this file.');
    }

    /**
     * Assemble several uploaded debug files at once.
     *
     * @param  array<string, array{name: string, chunks: list<string>, debugId?: string|null}>  $files  keyed by file checksum
     * @return array<string, AssembleStatus> keyed by file checksum
     */
    public function assembleDebugFiles(string $project, array $files): array
    {
        $payload = [];

        foreach ($files as $checksum => $file) {
            $payload[$checksum] = ['name' => $file['name'], 'chunks' => $file['chunks'], 'debug_id' => $file['debugId'] ?? null];
        }

        $statuses = [];

        foreach (self::object($this->connector->post($this->projectPath($project).'files/difs/assemble/', $payload)) as $checksum => $status) {
            if (is_array($status)) {
                /** @var array<string, mixed> $status */
                $statuses[(string) $checksum] = AssembleStatus::fromArray($status);
            }
        }

        return $statuses;
    }

    /**
     * @return CursorPage<DebugFile>
     */
    public function debugFiles(string $project, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->projectPath($project).'files/dsyms/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], DebugFile::fromArray(...));
    }

    /**
     * Upload a zip of ProGuard mapping files directly, without chunking.
     *
     * @return list<array<string, mixed>> one entry per stored file
     */
    public function uploadProguardMappings(string $project, string $zip, string $filename = 'mappings.zip'): array
    {
        $response = $this->connector->postFiles($this->projectPath($project).'files/dsyms/', 'file', [
            ['contents' => $zip, 'filename' => $filename],
        ]);

        /** @var list<array<string, mixed>> */
        return array_values(array_filter((array) $response->json(), is_array(...)));
    }

    private function gzip(string $contents): string
    {
        $compressed = gzencode($contents);

        if ($compressed === false) {
            throw new RuntimeException('Could not gzip an upload chunk.');
        }

        return $compressed;
    }

    private function chunkPath(): string
    {
        return "organizations/{$this->organization()}/chunk-upload/";
    }

    private function projectPath(string $project): string
    {
        return "projects/{$this->organization()}/".self::segment($project).'/';
    }
}
