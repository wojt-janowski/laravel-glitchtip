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

    /**
     * Create an organization. The token's user becomes its owner.
     */
    public function create(string $name): Organization
    {
        return Organization::fromArray(self::object($this->connector->post('organizations/', ['name' => $name])));
    }

    /**
     * Rename the configured organization, or the one given.
     */
    public function update(string $name, ?string $slug = null): Organization
    {
        return Organization::fromArray(self::object($this->connector->put($this->path($slug), ['name' => $name])));
    }

    /**
     * Delete the configured organization, or the one given, with all its projects and data.
     */
    public function delete(?string $slug = null): void
    {
        $this->connector->delete($this->path($slug));
    }

    /**
     * Copy projects, teams and members from another GlitchTip or Sentry
     * instance into the organization with the same slug here. Runs in the
     * request, so allow a generous timeout.
     *
     * @param  string  $url  the source instance's base URL
     * @param  string  $authToken  an API token for the source instance
     * @param  string|null  $organization  slug of the organization on both instances; defaults to the configured one
     */
    public function import(string $url, string $authToken, ?string $organization = null): void
    {
        $this->connector->post('import/', [
            'url' => $url,
            'authToken' => $authToken,
            'organizationSlug' => $organization ?? rawurldecode($this->organization()),
        ]);
    }

    private function path(?string $slug): string
    {
        return 'organizations/'.($slug === null ? $this->organization() : self::segment($slug)).'/';
    }
}
