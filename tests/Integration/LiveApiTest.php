<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use WojtJanowski\GlitchTip\Data\AlertInput;
use WojtJanowski\GlitchTip\Data\ChunkedFile;
use WojtJanowski\GlitchTip\Data\Commit;
use WojtJanowski\GlitchTip\Data\Issue;
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;
use WojtJanowski\GlitchTip\Data\MonitorInput;
use WojtJanowski\GlitchTip\Data\Project;
use WojtJanowski\GlitchTip\Data\ProjectInput;
use WojtJanowski\GlitchTip\Data\UserOptions;
use WojtJanowski\GlitchTip\Enums\IssueStatus;
use WojtJanowski\GlitchTip\Enums\MemberRole;
use WojtJanowski\GlitchTip\Enums\MonitorType;
use WojtJanowski\GlitchTip\Exceptions\RequestFailedException;
use WojtJanowski\GlitchTip\Exceptions\UnsupportedByServerException;
use WojtJanowski\GlitchTip\Tests\Integration\LiveRun;

/*
| Runs against a real GlitchTip organization. Skipped unless
| GLITCHTIP_INTEGRATION_URL, GLITCHTIP_INTEGRATION_TOKEN and
| GLITCHTIP_INTEGRATION_ORGANIZATION are set. Everything created is named
| "it-<timestamp>-<random>" and deleted afterwards. Status pages and
| repositories cannot be deleted through the API, so that test also needs
| GLITCHTIP_INTEGRATION_UNDELETABLE=1 and prints what to remove by hand.
*/

beforeEach(function (): void {
    $run = LiveRun::fromEnvironment();

    if (! $run instanceof LiveRun) {
        $this->markTestSkipped('Set GLITCHTIP_INTEGRATION_URL, GLITCHTIP_INTEGRATION_TOKEN and GLITCHTIP_INTEGRATION_ORGANIZATION to run against a real server.');
    }

    $this->run = $run;
});

afterEach(function (): void {
    if (property_exists($this, 'run') && $this->run !== null) {
        expect($this->run->runCleanups())->toBe([], 'Cleanup failed; remove these by hand');
    }
});

/**
 * Send an event to a project's DSN the way a Sentry SDK would.
 *
 * @param  array<string, mixed>  $event
 */
function ingest(LiveRun $run, Project $project, string $publicKey, array $event): void
{
    Http::withHeaders(['X-Sentry-Auth' => "Sentry sentry_version=7, sentry_client=laravel-glitchtip-it/1.0, sentry_key={$publicKey}"])
        ->post(rtrim($run->url, '/')."/api/{$project->id}/store/", $event + [
            'event_id' => str_replace('-', '', (string) Str::uuid()),
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'platform' => 'php',
            'level' => 'error',
        ])
        ->throw();
}

it('manages teams, projects, client keys and team access', function (): void {
    $run = $this->run;
    $g = $run->client;
    [$team, $project] = $run->teamAndProject();

    $renamed = $g->teams()->update($team->slug, $run->name('squad'));
    $run->defer('rename team back', fn () => $g->teams()->update($renamed->slug, $team->slug));
    expect($renamed->slug)->toBe($run->name('squad'));

    $updated = $g->projects()->update((string) $project->slug, new ProjectInput(name: $run->name('renamed'), slug: $project->slug));
    expect($updated->name)->toBe($run->name('renamed'));

    $second = $g->teams()->create($run->name('second'));
    $run->defer("team {$second->slug}", fn () => $g->teams()->delete($second->slug));

    $g->teams()->addToProject($second->slug, (string) $project->slug);
    expect(array_map(fn ($t) => $t->slug, $g->teams()->forProject((string) $project->slug)->items))->toContain($second->slug);

    $g->teams()->removeFromProject($second->slug, (string) $project->slug);
    expect(array_map(fn ($t) => $t->slug, $g->teams()->forProject((string) $project->slug)->items))->not->toContain($second->slug);

    $me = $g->instance()->whoami()->user;
    $member = collect($g->members()->list()->lazy())->first(fn ($m): bool => $m->email === $me?->email);
    expect($member)->not->toBeNull();

    $g->teams()->addMember($second->slug, $member->id);
    expect(array_map(fn ($m) => $m->id, $g->members()->forTeam($second->slug)->items))->toContain($member->id);
    $g->teams()->removeMember($second->slug, $member->id);

    $key = $g->projects()->createKey((string) $project->slug, $run->name('key'), rateLimitWindow: 60, rateLimitCount: 100);
    expect($key->dsn)->toContain(parse_url($run->url, PHP_URL_HOST));

    // GlitchTip 6.2 ignores key changes and returns the key unchanged.
    expect($g->projects()->updateKey((string) $project->slug, $key->id, $run->name('key2'))->id)->toBe($key->id)
        ->and($g->projects()->key((string) $project->slug, $key->id)->label)->toBe($run->name('key'));

    $g->projects()->deleteKey((string) $project->slug, $key->id);
    expect(fn () => $g->projects()->key((string) $project->slug, $key->id))->toThrow(RequestFailedException::class);
});

it('triages real issues from ingested events', function (): void {
    $run = $this->run;
    $g = $run->client;
    [, $project] = $run->teamAndProject();
    $slug = (string) $project->slug;
    $publicKey = $g->projects()->keys($slug)->items[0]->publicKey;
    $environment = $run->name('env');

    foreach (['ItAlphaException', 'ItAlphaException', 'ItBetaException'] as $type) {
        ingest($run, $project, $publicKey, [
            'environment' => $environment,
            'tags' => ['run' => $run->prefix],
            'exception' => ['values' => [['type' => $type, 'value' => "Raised by {$run->prefix}", 'stacktrace' => ['frames' => [
                ['filename' => 'app/Jobs/ItJob.php', 'function' => 'handle', 'lineno' => 42, 'in_app' => true],
            ]]]]],
        ]);
    }

    /** @var list<Issue> $issues */
    $issues = $run->eventually('both issues to be processed', function () use ($g, $slug): ?array {
        $issues = $g->issues()->forProject($slug)->items;

        return count($issues) >= 2 ? $issues : null;
    });

    $alpha = collect($issues)->first(fn (Issue $issue): bool => str_contains($issue->title, 'ItAlphaException'));
    $beta = collect($issues)->first(fn (Issue $issue): bool => str_contains($issue->title, 'ItBetaException'));
    expect($alpha)->not->toBeNull()->and($beta)->not->toBeNull();

    $run->eventually('the alpha issue to count both events', fn (): ?true => $g->issues()->get($alpha->id)->count >= 2 ? true : null);

    // Events and tags on real data.
    $latest = $g->events()->latest($alpha->id);
    expect($latest->tags['run'] ?? null)->toBe($run->prefix)
        ->and($latest->entry('exception'))->not->toBeNull()
        ->and($g->events()->forIssue($alpha->id)->count())->toBe(2)
        ->and(array_map(fn ($t) => $t->key, $g->issues()->tags($alpha->id)))->toContain('run')
        ->and($g->events()->raw($alpha->id, $latest->eventId)['environment'] ?? null)->toBe($environment);

    // Status and assignment.
    $me = $g->instance()->whoami()->user;
    expect($g->issues()->resolve($alpha->id)->status)->toBe(IssueStatus::Resolved)
        ->and($g->issues()->unresolve($alpha->id)->status)->toBe(IssueStatus::Unresolved)
        ->and($g->issues()->assign($alpha->id, "user:{$me?->id}")->assignedTo?->email)->toBe($me?->email)
        ->and($g->issues()->update($alpha->id, IssueUpdate::unassigned())->assignedTo)->toBeNull();

    // Comments.
    $comment = $g->comments()->create($alpha->id, 'Investigating');
    expect($g->comments()->update($alpha->id, (int) $comment->id, 'Fixed')->text)->toBe('Fixed')
        ->and(array_map(fn ($c) => $c->text, $g->comments()->list($alpha->id)->items))->toBe(['Fixed']);
    $g->comments()->delete($alpha->id, (int) $comment->id);
    expect($g->comments()->list($alpha->id)->items)->toBe([]);

    // Stats, hashes and bulk updates.
    expect($g->issues()->stats([(int) $alpha->id])[0]->count)->toBeGreaterThanOrEqual(2)
        ->and($g->issues()->hashes($alpha->id)->items)->not->toBe([]);

    $g->issues()->bulkUpdate(IssueFilter::ids([(int) $alpha->id, (int) $beta->id]), IssueUpdate::ignored());
    expect($g->issues()->get($alpha->id)->status)->toBe(IssueStatus::Ignored)
        ->and($g->issues()->get($beta->id)->status)->toBe(IssueStatus::Ignored);

    // The environment came from the events; hide and show it.
    expect($g->environments()->hide($slug, $environment)->isHidden)->toBeTrue()
        ->and($g->environments()->show($slug, $environment)->isHidden)->toBeFalse();

    // Short id lookup is newer than GlitchTip 6.2.x.
    if (! $g->server()->versionAtLeast('7.0.0')) {
        expect(fn () => $g->issues()->findByShortId($alpha->shortId))->toThrow(UnsupportedByServerException::class);
    } else {
        expect($g->issues()->findByShortId($alpha->shortId)->id)->toBe($alpha->id);
    }

    $g->issues()->delete($beta->id);
    expect(fn () => $g->issues()->get($beta->id))->toThrow(RequestFailedException::class);
});

it('records releases, commits and deploys', function (): void {
    $run = $this->run;
    $g = $run->client;
    [, $project] = $run->teamAndProject();
    $version = $run->name('api').'@1.0.0';

    $release = $g->releases()->create($version, [(string) $project->slug], ref: 'abc123');
    $run->defer("release {$version}", fn () => $g->releases()->delete($version));

    expect($release->version)->toBe($version)
        ->and($release->projects)->toBe([(string) $project->slug]);

    $withCommits = $g->releases()->setCommits($version, [
        new Commit(id: 'abc123', message: 'Fix checkout', authorName: 'IT', authorEmail: 'it@example.invalid'),
        new Commit(id: 'def456', message: 'Add retries'),
    ]);
    expect($withCommits->commitCount)->toBe(2)
        ->and(array_map(fn ($c) => $c->id, $g->releases()->commits($version)))->toBe(['abc123', 'def456']);

    $deploy = $g->releases()->createDeploy($version, 'production', url: 'https://example.invalid');
    expect($deploy->environment)->toBe('production')
        ->and($g->releases()->deploys($version))->toHaveCount(1);

    $finalized = $g->releases()->finalize($version);
    expect($finalized->dateReleased)->not->toBeNull()
        ->and($g->releases()->get($version, project: (string) $project->slug)->version)->toBe($version)
        ->and($g->releases()->files($version)->items)->toBe([]);
});

it('uploads and assembles an artifact bundle in chunks', function (): void {
    $run = $this->run;
    $g = $run->client;
    [, $project] = $run->teamAndProject();

    // Random contents, so no chunk already exists on the server.
    $contents = random_bytes(4096);
    $before = new ChunkedFile(checksum: sha1($contents), chunks: [sha1($contents)], size: 4096);

    $missing = $g->uploads()->assembleArtifactBundle($before, [(string) $project->slug]);
    expect($missing->state)->toBe('not_found')
        ->and($missing->missingChunks)->toBe([sha1($contents)]);

    $file = $g->uploads()->upload($contents);
    expect($file->chunks)->toBe([sha1($contents)]);

    $assembled = $g->uploads()->assembleArtifactBundle($file, [(string) $project->slug]);
    expect($assembled->needsChunks())->toBeFalse()
        ->and($assembled->state)->toBeIn(['created', 'ok']);
});

it('runs a heartbeat monitor without a token', function (): void {
    $run = $this->run;
    $g = $run->client;

    $monitor = $g->monitors()->create(new MonitorInput(name: $run->name('heartbeat'), type: MonitorType::Heartbeat, interval: 300, expectedStatus: null));
    $run->defer("monitor {$monitor->id}", fn () => $g->monitors()->delete((int) $monitor->id));

    expect($monitor->type)->toBe(MonitorType::Heartbeat)
        ->and($monitor->endpointId)->not->toBeNull();

    $check = $run->anonymous()->monitors()->heartbeat((string) $monitor->endpointId);
    expect($check->isUp)->toBeTrue();

    $run->eventually('the heartbeat check to be recorded', fn (): ?true => $g->monitors()->checks((int) $monitor->id)->count() > 0 ? true : null);

    $updated = $g->monitors()->update((int) $monitor->id, new MonitorInput(name: $run->name('heartbeat2'), type: MonitorType::Heartbeat, interval: 600, expectedStatus: null));
    expect($updated->name)->toBe($run->name('heartbeat2'))
        ->and($updated->interval)->toBe(600);
});

it('manages alerts, SSO apps and user options', function (): void {
    $run = $this->run;
    $g = $run->client;
    [, $project] = $run->teamAndProject();
    $slug = (string) $project->slug;

    $alert = $g->alerts()->create($slug, new AlertInput(name: $run->name('alert'), timespanMinutes: 5, quantity: 50));
    $run->defer("alert {$alert->id}", fn () => $g->alerts()->delete($slug, (int) $alert->id));
    expect($g->alerts()->update($slug, (int) $alert->id, new AlertInput(name: $run->name('alert2'), timespanMinutes: 10, quantity: 100))->quantity)->toBe(100);

    $app = $g->socialApps()->create($run->name('oidc'), clientId: 'it-client', clientSecret: 'it-secret', serverUrl: 'https://example.invalid');
    $run->defer("social app {$app->id}", fn () => $g->socialApps()->delete((int) $app->id));
    $updated = $g->socialApps()->update((int) $app->id, $run->name('oidc2'), 'it-client-2');
    expect($updated->clientId)->toBe('it-client-2')
        ->and($updated->serverUrl)->toBe($app->serverUrl);

    $original = $g->users()->get()->options;
    $run->defer('user options', fn () => $g->users()->update($original));
    $changed = $g->users()->update(new UserOptions(
        timezone: $original->timezone,
        stacktraceOrder: $original->stacktraceOrder,
        language: $original->language,
        clock24Hours: ! ($original->clock24Hours ?? false),
        preferredTheme: $original->preferredTheme,
    ));
    expect($changed->options->clock24Hours)->toBe(! ($original->clock24Hours ?? false));
});

it('invites a member and looks the invitation up without a token', function (): void {
    $run = $this->run;
    $g = $run->client;

    try {
        $member = $g->members()->invite($run->name('invitee').'@example.com', MemberRole::Member, sendEmail: false);
        $run->defer("invitation {$member->id}", fn () => $g->members()->delete($member->id));
        expect($member->pending)->toBeTrue()->and($member->inviteLink)->not->toBeNull();

        preg_match('#/accept/(\d+)/([^/]+)/?#', (string) $member->inviteLink, $link);
        $invitation = $run->anonymous()->members()->invitation((int) $link[1], $link[2]);
        expect($invitation->accepted)->toBeFalse()
            ->and($invitation->member->email)->toBe($member->email);
    } catch (RequestFailedException $exception) {
        // Instances with registration closed only allow inviting existing users.
        if ($exception->status === 403 && str_contains($exception->getMessage(), 'Only existing users')) {
            $this->markTestSkipped('This instance only lets existing users be invited.');
        }

        throw $exception;
    }
});

it('creates a status page and a repository, which need removing by hand', function (): void {
    if (getenv('GLITCHTIP_INTEGRATION_UNDELETABLE') !== '1') {
        $this->markTestSkipped('Set GLITCHTIP_INTEGRATION_UNDELETABLE=1 to create a status page and a repository, which the API cannot delete.');
    }

    $run = $this->run;
    $g = $run->client;

    $page = $g->monitors()->createStatusPage($run->name('status'));
    $repository = $g->repositories()->create($run->name('repo'), 'https://example.invalid/'.$run->prefix);

    expect($page->name)->toBe($run->name('status'))
        ->and($repository->name)->toBe($run->name('repo'));

    $admin = rtrim($run->url, '/').'/admin';
    fwrite(STDOUT, "\n  Remove by hand in Django admin: status page \"{$page->name}\" ({$admin}/uptime/statuspage/) and repository \"{$repository->name}\" ({$admin}/sourcecode/repository/).\n");
});
