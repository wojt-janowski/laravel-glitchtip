<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * The server's chunk upload limits.
 */
final readonly class ChunkUploadOptions
{
    /**
     * @param  int  $chunkSize  bytes per chunk, before compression
     * @param  list<string>  $compression
     * @param  list<string>  $accept  upload kinds the server accepts, e.g. "debug_files"
     */
    public function __construct(
        public string $url,
        public int $chunkSize,
        public int $chunksPerRequest,
        public int $maxFileSize,
        public int $maxRequestSize,
        public int $concurrency,
        public string $hashAlgorithm,
        public array $compression = [],
        public array $accept = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            url: $a->string('url'),
            chunkSize: $a->int('chunkSize'),
            chunksPerRequest: $a->int('chunksPerRequest'),
            maxFileSize: $a->int('maxFileSize'),
            maxRequestSize: $a->int('maxRequestSize'),
            concurrency: $a->int('concurrency'),
            hashAlgorithm: $a->string('hashAlgorithm'),
            compression: $a->strings('compression'),
            accept: $a->strings('accept'),
        );
    }
}
