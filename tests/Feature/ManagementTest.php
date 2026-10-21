<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WojtJanowski\GlitchTip\Data\Commit;
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;
use WojtJanowski\GlitchTip\Data\MonitorInput;
use WojtJanowski\GlitchTip\Data\UserOptions;
use WojtJanowski\GlitchTip\Enums\IssueStatus;
use WojtJanowski\GlitchTip\Enums\MemberRole;
use WojtJanowski\GlitchTip\Enums\MonitorType;
use WojtJanowski\GlitchTip\Enums\StatsCategory;
use WojtJanowski\GlitchTip\Enums\StatsInterval;
use WojtJanowski\GlitchTip\Exceptions\MissingConfigurationException;
use WojtJanowski\GlitchTip\Facades\GlitchTip;
use WojtJanowski\GlitchTip\GlitchTip as Client;
use WojtJanowski\GlitchTip\Resources\Server;
use WojtJanowski\GlitchTip\Support\Connector;

function withoutToken(): void
{
    config()->set('glitchtip.token');
    app()->forgetInstance(Client::class);
}

describe('uploads', function (): void {
    it('uploads gzipped chunks named by their SHA-1 and returns what to assemble', function (): void {
        Http::fake([
            'glitchtip.test/api/0/organizations/acme/chunk-upload/' => fn (Request $request) => $request->method() === 'GET'
                ? Http::response(['url' => '', 'chunkSize' => 4, 'chunksPerRequest' => 1, 'maxFileSize' => 100, 'maxRequestSize' => 4, 'concurrency' => 1, 'hashAlgorithm' => 'sha1', 'compression' => ['gzip'], 'accept' => ['artifact_bundles']])
                : Http::response(),
        ]);

        $file = GlitchTip::uploads()->upload('abcdefghij');

        expect($file->checksum)->toBe(sha1('abcdefghij'))
            ->and($file->chunks)->toBe([sha1('abcd'), sha1('efgh'), sha1('ij')])
            ->and($file->size)->toBe(10);

        $uploads = Http::recorded(fn (Request $request): bool => $request->method() === 'POST')->map(fn (array $pair) => $pair[0]);

        expect($uploads)->toHaveCount(3);

        $first = $uploads->first();
        $part = collect($first->data())->firstWhere('name', 'file_gzip');

        expect($first->isMultipart())->toBeTrue()
            ->and($part['filename'])->toBe(sha1('abcd'))
            ->and(gzdecode($part['contents']))->toBe('abcd');
    });

    it('maps debug file assemble results by checksum', function (): void {
        Http::fake(['*' => Http::response(['c1' => ['state' => 'not_found', 'missingChunks' => ['x']]])]);

        $statuses = GlitchTip::uploads()->assembleDebugFiles('backend', ['c1' => ['name' => 'app.dSYM', 'chunks' => ['x'], 'debugId' => 'dbg']]);

        expect($statuses['c1']->needsChunks())->toBeTrue()
            ->and($statuses['c1']->failed())->toBeFalse();

        Http::assertSent(fn (Request $request): bool => $request->data() === ['c1' => ['name' => 'app.dSYM', 'chunks' => ['x'], 'debug_id' => 'dbg']]);
    });
});

describe('members', function (): void {
    it('invites with team slugs or slug-to-role pairs', function (): void {
        Http::fake(['*' => Http::response(['id' => '5', 'email' => 'new@example.com', 'role' => 'admin', 'roleName' => 'Admin', 'dateCreated' => '2026-10-21T00:00:00Z', 'pending' => true, 'isOwner' => false, 'inviteLink' => 'https://glitchtip.test/accept/5/t/'])]);

        $member = GlitchTip::members()->invite('new@example.com', MemberRole::Admin, ['core', 'web' => 'admin'], sendEmail: false);

        expect($member->role)->toBe(MemberRole::Admin)
            ->and($member->pending)->toBeTrue()
            ->and($member->inviteLink)->toBe('https://glitchtip.test/accept/5/t/');

        Http::assertSent(fn (Request $request): bool => $request['teamRoles'] === [['teamSlug' => 'core', 'role' => ''], ['teamSlug' => 'web', 'role' => 'admin']]
            && $request['sendInvite'] === false
            && $request['orgRole'] === 'admin');
    });

    it('leaves teams alone when updating only the role', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::members()->update(5, MemberRole::Manager);

        Http::assertSent(fn (Request $request): bool => $request->data() === ['orgRole' => 'manager']);
    });

    it('reads an invitation without a token', function (): void {
        withoutToken();
        Http::fake(['*' => Http::response(['acceptInvite' => false, 'orgUser' => [
            'id' => '5', 'email' => 'new@example.com', 'role' => 'member', 'roleName' => 'Member', 'dateCreated' => '2026-10-21T00:00:00Z', 'pending' => true, 'isOwner' => false,
            'organization' => ['id' => '1', 'slug' => 'acme', 'name' => 'Acme', 'dateCreated' => '2026-01-01T00:00:00Z'],
        ]])]);

        $invitation = GlitchTip::members()->invitation(5, 'tok');

        expect($invitation->accepted)->toBeFalse()
            ->and($invitation->member->email)->toBe('new@example.com')
            ->and($invitation->organization?->slug)->toBe('acme');

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
    });
});

describe('requests without a token', function (): void {
    it('reads the instance settings', function (): void {
        withoutToken();
        Http::fake(['*' => Http::response(['version' => '6.2.6'])]);

        expect(GlitchTip::server()->version)->toBe('6.2.6');
    });

    it('records a heartbeat', function (): void {
        withoutToken();
        Http::fake(['*' => Http::response(['startCheck' => '2026-10-21T00:00:00Z', 'isUp' => true])]);

        expect(GlitchTip::monitors()->heartbeat('endpoint-uuid')->isUp)->toBeTrue();
    });

    it('reads and clears sentry-wizard data with only the hash', function (): void {
        withoutToken();
        Http::fake(['*' => Http::response(['projects' => []])]);

        expect(GlitchTip::wizard()->data('wizard-hash'))->toBe(['projects' => []]);
        GlitchTip::wizard()->delete('wizard-hash');

        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request): bool => $request->hasHeader('Authorization'));
    });

    it('keeps the deprecated Server resource working', function (): void {
        Http::fake(['*' => Http::response(['version' => '6.2.6'])]);

        $server = new Server(new Connector(app(Factory::class), 'https://glitchtip.test', 'test-token'));

        expect($server->info()->version)->toBe('6.2.6');
    });

    it('still requires a token for everything else', function (): void {
        withoutToken();

        GlitchTip::instance()->whoami();
    })->throws(MissingConfigurationException::class, 'token');
});

describe('request bodies', function (): void {
    it('sends release commits as a JSON array', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::releases()->setCommits('1.4.0', [new Commit(id: 'abc', message: 'Fix', authorName: 'Wojt')]);

        Http::assertSent(fn (Request $request): bool => $request->body() === '[{"id":"abc","message":"Fix","authorName":"Wojt","authorEmail":""}]');
    });

    it('sends an email address in the body of a DELETE', function (): void {
        Http::fake(['*' => Http::response(status: 204)]);

        GlitchTip::users()->deleteEmail('old@example.com');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/api/0/users/me/emails/')
            && $request['email'] === 'old@example.com');
    });

    it('sets a key rate limit only when both parts are given', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::projects()->createKey('backend', 'CI', rateLimitWindow: 60, rateLimitCount: 100);
        GlitchTip::projects()->createKey('backend', 'CLI', rateLimitWindow: 60);

        Http::assertSent(fn (Request $request): bool => $request->data() === ['name' => 'CI', 'rateLimit' => ['window' => 60, 'count' => 100]]);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['name' => 'CLI']);
    });

    it('scopes a project bulk update by status', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::issues()->bulkUpdateForProject('backend', IssueFilter::everything(), IssueUpdate::resolved(), IssueStatus::Ignored);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://glitchtip.test/api/0/projects/acme/backend/issues/?status=ignored');
    });

    it('sends the current user name back unless a new one is given', function (): void {
        Http::fake(['*' => Http::response(['id' => '1', 'email' => 'wojt@example.com', 'username' => 'wojt@example.com', 'name' => 'Wojt', 'dateJoined' => '2026-10-18T00:00:00Z', 'hasPasswordAuth' => true, 'options' => []])]);

        GlitchTip::users()->update(new UserOptions(timezone: 'UTC'));
        GlitchTip::users()->update(new UserOptions(timezone: 'UTC'), 'Wojt J');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request['name'] === 'Wojt' && $request['options']['timezone'] === 'UTC');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request['name'] === 'Wojt J');
        Http::assertSentCount(3);
    });

    it('refuses to delete no hashes', function (): void {
        GlitchTip::issues()->deleteHashes(42, []);
    })->throws(InvalidArgumentException::class);
});

describe('monitors', function (): void {
    it('sends an empty url for monitors without one', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::monitors()->update(5, new MonitorInput(name: 'Nightly', type: MonitorType::Heartbeat, expectedStatus: null));

        Http::assertSent(fn (Request $request): bool => $request['url'] === '');
    });
});

describe('responses', function (): void {
    it('keys alert preferences by project id', function (): void {
        Http::fake(['*' => Http::response(['7' => 1, '9' => -1])]);

        expect(GlitchTip::projects()->alertPreferences())->toBe([7 => 1, 9 => -1]);
    });

    it('parses the stats series for the requested field', function (): void {
        Http::fake(['*' => Http::response([
            'intervals' => ['2026-10-01T00:00:00+00:00', '2026-10-01T01:00:00+00:00'],
            'groups' => [['series' => ['sum(quantity)' => [4, null]]]],
        ])]);

        $series = GlitchTip::stats()->series(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-02'), StatsCategory::Transaction);

        expect($series->values)->toBe([4, null])
            ->and($series->toMap())->toBe(['2026-10-01T00:00:00+00:00' => 4, '2026-10-01T01:00:00+00:00' => null]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'category=transaction&field=sum%28quantity%29&interval=1h'));
    });

    it('adds up hours into days, because GlitchTip answers 1d with only the first hour of each day', function (): void {
        Http::fake(['*/stats_v2/*' => Http::response([
            'intervals' => ['2026-10-20T22:00:00+00:00', '2026-10-20T23:00:00+00:00', '2026-10-21T00:00:00+00:00', '2026-10-21T01:00:00+00:00', '2026-10-22T00:00:00+00:00'],
            'groups' => [['series' => ['sum(quantity)' => [3, null, 22, 29, null]]]],
        ])]);

        $series = GlitchTip::stats()->series(CarbonImmutable::parse('2026-10-20T22:00Z'), CarbonImmutable::parse('2026-10-22T01:00Z'), interval: StatsInterval::Day);

        expect($series->toMap())->toBe([
            '2026-10-20T00:00:00+00:00' => 3,
            '2026-10-21T00:00:00+00:00' => 51,
            '2026-10-22T00:00:00+00:00' => null,
        ]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'interval=1h'));
    });

    it('keeps fractional uptime and log usage', function (): void {
        Http::fake(['*' => Http::response(['total' => 10, 'eventCount' => 4, 'transactionEventCount' => 3, 'uptimeCheckEventCount' => 2.5, 'logEventCount' => 0.5, 'fileSizeMb' => 0])]);

        $usage = GlitchTip::billing()->usage();

        expect($usage->uptimeCheckEventCount)->toBe(2.5)
            ->and($usage->logEventCount)->toBe(0.5);
    });

    it('returns no subscription when the organization has none', function (): void {
        Http::fake(['*' => Http::response('null')]);

        expect(GlitchTip::billing()->subscription())->toBeNull();
    });

    it('reports the token scopes', function (): void {
        Http::fake(['*' => Http::response(['version' => '0', 'user' => null, 'auth' => ['id' => 3, 'label' => 'CI', 'scopes' => ['event:read'], 'created' => '2026-10-18T00:00:00Z', 'token' => 'secret']])]);

        $auth = GlitchTip::instance()->whoami();

        expect($auth->hasScope('event:read'))->toBeTrue()
            ->and($auth->hasScope('event:write'))->toBeFalse()
            ->and(property_exists($auth->token, 'token'))->toBeFalse();
    });

    it('maps issue stats series', function (): void {
        Http::fake(['*' => Http::response([['id' => '42', 'count' => '17', 'userCount' => 3, 'firstSeen' => '2026-10-01T00:00:00Z', 'lastSeen' => '2026-10-20T00:00:00Z', 'isUnhandled' => true, 'stats' => ['24h' => [[1760000000, 2], [1760003600, 5]]]]])]);

        $stats = GlitchTip::issues()->stats([42])[0];

        expect($stats->count)->toBe(17)
            ->and($stats->series['24h'])->toBe([[1760000000, 2], [1760003600, 5]]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'groups=42&statsPeriod=24h'));
    });
});
