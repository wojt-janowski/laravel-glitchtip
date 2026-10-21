<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip;

use InvalidArgumentException;
use WojtJanowski\GlitchTip\Data\ServerInfo;
use WojtJanowski\GlitchTip\Resources\Alerts;
use WojtJanowski\GlitchTip\Resources\Billing;
use WojtJanowski\GlitchTip\Resources\Comments;
use WojtJanowski\GlitchTip\Resources\Environments;
use WojtJanowski\GlitchTip\Resources\Events;
use WojtJanowski\GlitchTip\Resources\Instance;
use WojtJanowski\GlitchTip\Resources\Issues;
use WojtJanowski\GlitchTip\Resources\Logs;
use WojtJanowski\GlitchTip\Resources\Members;
use WojtJanowski\GlitchTip\Resources\Monitors;
use WojtJanowski\GlitchTip\Resources\Organizations;
use WojtJanowski\GlitchTip\Resources\Performance;
use WojtJanowski\GlitchTip\Resources\Projects;
use WojtJanowski\GlitchTip\Resources\Releases;
use WojtJanowski\GlitchTip\Resources\Repositories;
use WojtJanowski\GlitchTip\Resources\SocialApps;
use WojtJanowski\GlitchTip\Resources\Stats;
use WojtJanowski\GlitchTip\Resources\Teams;
use WojtJanowski\GlitchTip\Resources\Uploads;
use WojtJanowski\GlitchTip\Resources\Users;
use WojtJanowski\GlitchTip\Resources\Wizard;
use WojtJanowski\GlitchTip\Support\BrowserSession;
use WojtJanowski\GlitchTip\Support\Connector;

final readonly class GlitchTip
{
    public function __construct(
        private Connector $connector,
        private ?string $organization = null,
    ) {}

    /**
     * A separate client for another GlitchTip account. It leaves config and
     * this client untouched, so clients for several accounts can be used side
     * by side. The default organization is not carried over, because it
     * belongs to the configured account.
     *
     * @param  string  $url  the instance base URL, e.g. https://app.glitchtip.com
     */
    public function using(string $url, string $token, ?string $organization = null): self
    {
        if (trim($url) === '' || trim($token) === '') {
            throw new InvalidArgumentException('A GlitchTip account needs a URL and an API token.');
        }

        return new self($this->connector->withCredentials($url, $token), $organization);
    }

    /**
     * A client that acts as a user signed in to GlitchTip's own UI, using the
     * `sessionid` and `csrftoken` cookies their browser sent. It only works
     * for an app served from the GlitchTip instance's origin, which is where
     * the browser sends those cookies. Like using(), it leaves this client
     * and config untouched.
     *
     * @param  string  $url  the instance base URL, e.g. https://glitchtip.example.com
     */
    public function usingSession(string $url, string $sessionId, string $csrfToken, ?string $organization = null): self
    {
        if (trim($url) === '') {
            throw new InvalidArgumentException('A GlitchTip account needs a URL.');
        }

        return new self($this->connector->withSession($url, new BrowserSession($sessionId, $csrfToken)), $organization);
    }

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
     * The base URL of the instance this client talks to.
     */
    public function url(): string
    {
        return $this->connector->url();
    }

    /**
     * The server's version and enabled features.
     */
    public function server(): ServerInfo
    {
        return $this->instance()->settings();
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

    /**
     * Instance settings, license and the current credentials.
     */
    public function instance(): Instance
    {
        return new Instance($this->connector, $this->organization);
    }

    /**
     * Organization members and invitations.
     */
    public function members(): Members
    {
        return new Members($this->connector, $this->organization);
    }

    /**
     * The organization's own single sign-on providers.
     */
    public function socialApps(): SocialApps
    {
        return new SocialApps($this->connector, $this->organization);
    }

    /**
     * The token's user account.
     */
    public function users(): Users
    {
        return new Users($this->connector, $this->organization);
    }

    /**
     * Stripe billing, on instances with billing enabled.
     */
    public function billing(): Billing
    {
        return new Billing($this->connector, $this->organization);
    }

    /**
     * Source repositories linked to the organization.
     */
    public function repositories(): Repositories
    {
        return new Repositories($this->connector, $this->organization);
    }

    /**
     * Chunked uploads for source maps, release artifacts and debug files.
     */
    public function uploads(): Uploads
    {
        return new Uploads($this->connector, $this->organization);
    }

    /**
     * Organization-wide event volume over time.
     */
    public function stats(): Stats
    {
        return new Stats($this->connector, $this->organization);
    }

    /**
     * The sentry-wizard setup handshake.
     */
    public function wizard(): Wizard
    {
        return new Wizard($this->connector, $this->organization);
    }
}
