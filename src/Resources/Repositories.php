<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Repository;
use WojtJanowski\GlitchTip\Support\CursorPage;

/**
 * Source repositories linked to the organization.
 */
final readonly class Repositories extends Resource
{
    /**
     * @return CursorPage<Repository>
     */
    public function list(?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path(), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Repository::fromArray(...));
    }

    /**
     * @param  array<string, mixed>|null  $provider  e.g. ['id' => 'integrations:github', 'name' => 'GitHub']
     */
    public function create(string $name, string $url = '', ?array $provider = null): Repository
    {
        return Repository::fromArray(self::object($this->connector->post($this->path(), [
            'name' => $name,
            'url' => $url,
            'provider' => $provider,
        ])));
    }

    private function path(): string
    {
        return "organizations/{$this->organization()}/repos/";
    }
}
