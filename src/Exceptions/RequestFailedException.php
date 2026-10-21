<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * Thrown when GlitchTip answers with a non-2xx status.
 */
final class RequestFailedException extends GlitchTipException
{
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly string $method,
        public readonly string $path,
        public readonly ?Response $response = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(string $method, string $path, Response $response): self
    {
        $detail = $response->json('detail');

        // Validation failures come back as a list of {loc, msg, type} objects.
        if (is_array($detail)) {
            $detail = collect($detail)
                ->map(fn (mixed $error): string => is_array($error) && isset($error['msg']) && is_string($error['msg'])
                    ? $error['msg']
                    : (string) json_encode($error))
                ->implode('; ');
        }

        $reason = is_string($detail) && $detail !== '' ? $detail : $response->reason();

        return new self(
            message: sprintf('GlitchTip %s %s failed with %d: %s', $method, $path, $response->status(), $reason),
            status: $response->status(),
            method: $method,
            path: $path,
            response: $response,
        );
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }

    public function isUnauthorized(): bool
    {
        return $this->status === 401 || $this->status === 403;
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }
}
