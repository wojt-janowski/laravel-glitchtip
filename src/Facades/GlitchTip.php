<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Facades;

use Illuminate\Support\Facades\Facade;
use WojtJanowski\GlitchTip\GlitchTip as Client;
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
use WojtJanowski\GlitchTip\Resources\Teams;

/**
 * @method static Client forOrganization(string $organization)
 * @method static string|null organizationSlug()
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
