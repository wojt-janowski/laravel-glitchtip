<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * The result of asking GlitchTip to assemble uploaded chunks into a file.
 */
final readonly class AssembleStatus
{
    /**
     * @param  string  $state  "ok", "created", "not_found" or "error"
     * @param  list<string>  $missingChunks  chunk checksums still to upload
     */
    public function __construct(
        public string $state,
        public array $missingChunks = [],
        public ?string $detail = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            state: $a->string('state'),
            missingChunks: $a->strings('missingChunks'),
            detail: $a->nullableString('detail'),
        );
    }

    public function needsChunks(): bool
    {
        return $this->missingChunks !== [];
    }

    public function failed(): bool
    {
        return $this->state === 'error';
    }
}
