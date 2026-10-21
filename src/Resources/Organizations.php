<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Organization;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Organizations extends Resource
{
    /**
     * Organizations the token's user belongs to.
     *
     * @return CursorPage<Organization>
     */
    public function list(?string $query = null, ?bool $owner = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page('organizations/', [
            'query' => $query,
            'owner' => $owner,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Organization::fromArray(...));
    }

    /**
     * The configured organization, or the one given.
     */
    public function get(?string $slug = null): Organization
    {
        $slug = $slug === null ? $this->organization() : self::segment($slug);

        return Organization::fromArray(self::object($this->connector->get("organizations/{$slug}/")));
    }
}
