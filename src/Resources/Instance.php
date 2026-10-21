<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\CurrentAuth;
use WojtJanowski\GlitchTip\Data\InstanceLicense;
use WojtJanowski\GlitchTip\Data\LoginProvider;
use WojtJanowski\GlitchTip\Data\ServerInfo;

/**
 * Facts about the GlitchTip instance and the current credentials.
 */
final readonly class Instance extends Resource
{
    /**
     * Version and enabled features. Needs no token.
     */
    public function settings(): ServerInfo
    {
        return ServerInfo::fromArray(self::object($this->connector->getPublic('/api/settings/')));
    }

    /**
     * Who the token belongs to and which scopes it has.
     */
    public function whoami(): CurrentAuth
    {
        return CurrentAuth::fromArray(self::object($this->connector->get('')));
    }

    /**
     * Sign-in options on an organization's own login page. Needs no token;
     * unknown slugs return an empty list.
     *
     * @return list<LoginProvider>
     */
    public function loginProviders(?string $organization = null): array
    {
        $slug = $organization === null ? $this->organization() : self::segment($organization);

        /** @var list<array<string, mixed>> $apps */
        $apps = array_values(array_filter((array) $this->connector->getPublic("/api/settings/{$slug}/")->json('socialApps', []), is_array(...)));

        return array_map(LoginProvider::fromArray(...), $apps);
    }

    public function license(): InstanceLicense
    {
        return InstanceLicense::fromArray(self::object($this->connector->get('instance-license/')));
    }

    /**
     * The instance's support URL.
     */
    public function supportLink(): string
    {
        return (string) $this->connector->get('instance-license/support-link/')->json('url', '');
    }
}
