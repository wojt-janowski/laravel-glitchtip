<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * Who the client is authenticated as, from GET /api/0/.
 */
final readonly class CurrentAuth
{
    /**
     * @param  string  $version  API version, always "0"
     */
    public function __construct(
        public string $version,
        public ?User $user = null,
        public ?ApiToken $token = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            version: $a->string('version'),
            user: $a->map('user', User::fromArray(...)),
            token: $a->map('auth', ApiToken::fromArray(...)),
        );
    }

    public function hasScope(string $scope): bool
    {
        return $this->token instanceof ApiToken && in_array($scope, $this->token->scopes, true);
    }
}
