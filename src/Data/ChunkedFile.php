<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

/**
 * A file uploaded in chunks, ready to assemble. The checksums are SHA-1
 * hashes of the uncompressed content.
 */
final readonly class ChunkedFile
{
    /**
     * @param  string  $checksum  SHA-1 of the whole file
     * @param  list<string>  $chunks  SHA-1 of each chunk, in order
     */
    public function __construct(
        public string $checksum,
        public array $chunks,
        public int $size,
    ) {}
}
