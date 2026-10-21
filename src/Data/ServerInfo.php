<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Support\Attributes;

/**
 * What a GlitchTip instance reports about itself at /api/settings/.
 */
final readonly class ServerInfo
{
    /**
     * @param  string  $version  e.g. "6.2.6" or "7.0.0rc2"
     * @param  list<string>  $enabledFeatures  e.g. ['logs', 'uptime', 'mcp', 'email']
     */
    public function __construct(
        public string $version,
        public array $enabledFeatures = [],
        public ?string $instanceName = null,
        public ?string $environment = null,
        public string $serverTimeZone = 'UTC',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        return new self(
            version: $a->string('version'),
            enabledFeatures: $a->strings('enabledFeatures'),
            instanceName: $a->nullableString('glitchtipInstanceName'),
            environment: $a->nullableString('environment'),
            serverTimeZone: $a->string('serverTimeZone', 'UTC'),
        );
    }

    /**
     * Whether the server is at least the given version. Pre-releases of that
     * version count, so "7.0.0rc2" satisfies "7.0.0".
     */
    public function versionAtLeast(string $version): bool
    {
        return $this->version !== '' && version_compare($this->version, $version.'-dev', '>=');
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->enabledFeatures, true);
    }
}
