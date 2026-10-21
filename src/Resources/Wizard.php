<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

/**
 * The handshake sentry-wizard uses to configure a project: start() makes a
 * hash, authorize() stores the user's projects and DSNs under it, and the
 * wizard reads them with data().
 */
final readonly class Wizard extends Resource
{
    /**
     * Start a wizard session. Needs no token.
     */
    public function start(): string
    {
        return (string) $this->connector->getPublic('wizard/')->json('hash', '');
    }

    /**
     * Store the token user's projects, DSN keys and a releases token under the hash.
     */
    public function authorize(string $hash): void
    {
        $this->connector->post('wizard-set-token/', ['hash' => $hash]);
    }

    /**
     * The project data stored under a hash.
     *
     * @return array<string, mixed>
     */
    public function data(string $hash): array
    {
        return self::object($this->connector->get($this->path($hash)));
    }

    /**
     * Remove a hash and the credentials stored under it.
     */
    public function delete(string $hash): void
    {
        $this->connector->delete($this->path($hash));
    }

    private function path(string $hash): string
    {
        return 'wizard/'.self::segment($hash).'/';
    }
}
