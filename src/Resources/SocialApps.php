<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\OrganizationSocialApp;
use WojtJanowski\GlitchTip\Enums\SocialAppProvider;

/**
 * An organization's own single sign-on providers (OpenID Connect, Google).
 */
final readonly class SocialApps extends Resource
{
    /**
     * @return list<OrganizationSocialApp>
     */
    public function list(): array
    {
        return self::listOf($this->connector->get($this->path()), OrganizationSocialApp::fromArray(...));
    }

    /**
     * @param  string|null  $serverUrl  the OpenID Connect issuer URL
     */
    public function create(
        string $name,
        string $clientId,
        string $clientSecret,
        SocialAppProvider $provider = SocialAppProvider::OpenIdConnect,
        ?string $serverUrl = null,
    ): OrganizationSocialApp {
        return OrganizationSocialApp::fromArray(self::object($this->connector->post($this->path(), [
            'name' => $name,
            'clientID' => $clientId,
            'clientSecret' => $clientSecret,
            'provider' => $provider->value,
            'serverUrl' => $serverUrl,
        ])));
    }

    /**
     * @param  string|null  $clientSecret  null keeps the current secret
     */
    public function update(int $app, string $name, string $clientId, ?string $clientSecret = null, ?string $serverUrl = null): OrganizationSocialApp
    {
        return OrganizationSocialApp::fromArray(self::object($this->connector->put($this->path($app), array_filter([
            'name' => $name,
            'clientID' => $clientId,
            'clientSecret' => $clientSecret,
            'serverUrl' => $serverUrl,
        ], fn (mixed $value): bool => $value !== null))));
    }

    public function delete(int $app): void
    {
        $this->connector->delete($this->path($app));
    }

    private function path(?int $app = null): string
    {
        $path = "organizations/{$this->organization()}/social-apps/";

        return $app === null ? $path : $path.$app.'/';
    }
}
