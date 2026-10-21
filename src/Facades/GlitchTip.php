<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Facades;

use Illuminate\Support\Facades\Facade;
use WojtJanowski\GlitchTip\Data\ServerInfo;
use WojtJanowski\GlitchTip\GlitchTip as Client;
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

/**
 * @method static Client using(string $url, string $token, string|null $organization = null)
 * @method static Client usingSession(string $url, string $sessionId, string $csrfToken, string|null $organization = null)
 * @method static Client forOrganization(string $organization)
 * @method static string|null organizationSlug()
 * @method static string url()
 * @method static ServerInfo server()
 * @method static Organizations organizations()
 * @method static Projects projects()
 * @method static Teams teams()
 * @method static Environments environments()
 * @method static Issues issues()
 * @method static Events events()
 * @method static Comments comments()
 * @method static Releases releases()
 * @method static Monitors monitors()
 * @method static Alerts alerts()
 * @method static Logs logs()
 * @method static Performance performance()
 * @method static Wizard wizard()
 * @method static Stats stats()
 * @method static Uploads uploads()
 * @method static Repositories repositories()
 * @method static Billing billing()
 * @method static Users users()
 * @method static SocialApps socialApps()
 * @method static Members members()
 * @method static Instance instance()
 *
 * @see Client
 */
final class GlitchTip extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
