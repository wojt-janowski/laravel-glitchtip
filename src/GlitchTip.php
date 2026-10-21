<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip;

use WojtJanowski\GlitchTip\Data\ServerInfo;
use WojtJanowski\GlitchTip\Resources\Alerts;
use WojtJanowski\GlitchTip\Resources\Comments;
use WojtJanowski\GlitchTip\Resources\Environments;
use WojtJanowski\GlitchTip\Resources\Events;
use WojtJanowski\GlitchTip\Resources\Issues;
use WojtJanowski\GlitchTip\Resources\Logs;
use WojtJanowski\GlitchTip\Resources\Monitors;
use WojtJanowski\GlitchTip\Resources\Organizations;
use WojtJanowski\GlitchTip\Resources\Performance;
use WojtJanowski\GlitchTip\Resources\Projects;
use WojtJanowski\GlitchTip\Resources\Releases;
use WojtJanowski\GlitchTip\Resources\Server;
use WojtJanowski\GlitchTip\Resources\Teams;
use WojtJanowski\GlitchTip\Support\Connector;

final readonly class GlitchTip
{
    public function __construct(
        private Connector $connector,
        private ?string $organization = null,
    ) {}

    /**
     * A client scoped to another organization slug.
     */
    public function forOrganization(string $organization): self
    {
        return new self($this->connector, $organization);
    }

    public function organizationSlug(): ?string
    {
        return $this->organization;
    }

    /**
     * The server's version and enabled features.
     */
    public function server(): ServerInfo
    {
        return new Server($this->connector)->info();
    }

    public function organizations(): Organizations
    {
        return new Organizations($this->connector, $this->organization);
    }

    public function projects(): Projects
    {
        return new Projects($this->connector, $this->organization);
    }

    public function teams(): Teams
    {
        return new Teams($this->connector, $this->organization);
    }

    public function environments(): Environments
    {
        return new Environments($this->connector, $this->organization);
    }

    public function issues(): Issues
    {
        return new Issues($this->connector, $this->organization);
    }

    public function events(): Events
    {
        return new Events($this->connector, $this->organization);
    }

    public function comments(): Comments
    {
        return new Comments($this->connector, $this->organization);
    }

    public function releases(): Releases
    {
        return new Releases($this->connector, $this->organization);
    }

    public function monitors(): Monitors
    {
        return new Monitors($this->connector, $this->organization);
    }

    public function alerts(): Alerts
    {
        return new Alerts($this->connector, $this->organization);
    }

    public function logs(): Logs
    {
        return new Logs($this->connector, $this->organization);
    }

    public function performance(): Performance
    {
        return new Performance($this->connector, $this->organization);
    }
}
