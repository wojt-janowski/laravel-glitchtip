# laravel-glitchtip

A typed Laravel client for the [GlitchTip](https://glitchtip.com) error-tracking API. It covers the whole management API: issues and events, releases and source maps, uptime monitors, alerts, logs and performance data, plus organizations, members, teams, projects, users and billing. It works with the hosted service or a self-hosted install.

## Features

- Typed, readonly DTOs and enums for every response, not raw arrays
- Cursor pagination over GlitchTip's `Link` header, with `lazy()` iteration across pages
- Issue triage: resolve, ignore, assign, resolve in a release, merge, and bulk update or delete
- Release and deploy tracking for CI and deploy scripts
- Uptime monitors, heartbeats, status pages, project alerts, logs, transaction groups, span groups and N+1 detection
- Administration: organizations, members and invitations, teams, projects and client keys, single sign-on, users and billing
- Chunked uploads for source map bundles, release artifacts and debug files, the way sentry-cli does them
- Every operation in GlitchTip's OpenAPI spec is either implemented or excluded with a stated reason, and a test enforces it (see [API coverage](#api-coverage))
- Retries on connection failures, 429 and 5xx gateway responses for idempotent requests only, so a POST is never duplicated
- A single `RequestFailedException` that carries GlitchTip's error detail
- Testable with `Http::fake()`

## Requirements

- PHP 8.4+
- Laravel 13+

## Installation

```bash
composer require wojt-janowski/laravel-glitchtip
```

Add your credentials to `.env`:

```dotenv
GLITCHTIP_TOKEN=your-api-token
GLITCHTIP_ORGANIZATION=your-org-slug
# Only for self-hosted installs:
GLITCHTIP_URL=https://glitchtip.example.com
```

To create a token, go to **Profile → Auth Tokens** in GlitchTip. Grant only the scopes you need: `org:read`, `project:read` and `event:read` are enough for read-only use. Add `event:write` for triage and `project:releases` for releases.

Optionally, publish the config:

```bash
php artisan vendor:publish --tag=glitchtip-config
```

## Configuration

| Key | Env | Default | Description |
|---|---|---|---|
| `url` | `GLITCHTIP_URL` | `https://app.glitchtip.com` | Instance base URL, without `/api` |
| `token` | `GLITCHTIP_TOKEN` | — | API token (bearer) |
| `organization` | `GLITCHTIP_ORGANIZATION` | — | Default organization slug |
| `timeout` | `GLITCHTIP_TIMEOUT` | `30` | Request timeout in seconds |
| `connect_timeout` | `GLITCHTIP_CONNECT_TIMEOUT` | `10` | Connect timeout in seconds |
| `retry.times` | `GLITCHTIP_RETRY_TIMES` | `2` | Retries for GET/PUT/DELETE |
| `retry.sleep_milliseconds` | `GLITCHTIP_RETRY_SLEEP` | `500` | Delay between retries |

## Usage

Use the facade, or inject `WojtJanowski\GlitchTip\GlitchTip`. Calls that need an organization use the configured slug. To target a different organization, call `forOrganization()` first:

```php
use WojtJanowski\GlitchTip\Facades\GlitchTip;

GlitchTip::issues()->list();
GlitchTip::forOrganization('other-org')->issues()->list();
```

### Multiple accounts

To work with several GlitchTip accounts, servers or tokens at once, call `using()`. It returns a separate client and never changes config or the default client, so data from different accounts cannot mix, even when one request reads from all of them:

```php
use WojtJanowski\GlitchTip\Facades\GlitchTip;

$inbox = $accounts->flatMap(fn ($account) => GlitchTip::using($account->url, $account->token, $account->organization)
    ->issues()
    ->list(query: 'is:unresolved')
    ->collect()
    ->map(fn ($issue) => ['account' => $account->id, 'issue' => $issue]));
```

Each client keeps its own server and token for every call it makes, including fetching later pages. Timeouts and retry settings come from config.

The configured default organization is not carried over to another account. Pass the organization to `using()`, or call `forOrganization()` on the new client. You don't need a default account in `.env` if you only use `using()`.

### Acting as a signed-in user

GlitchTip's own UI signs users in with a Django session cookie, not a token. An app served from the same origin as the GlitchTip instance, for example behind a reverse proxy that sends `/api/` and `/_allauth/` to GlitchTip, receives that user's `sessionid` and `csrftoken` cookies on every request. Pass them to `usingSession()` to act as that user, with their role and under their name in GlitchTip:

```php
$glitchTip = GlitchTip::usingSession(
    url: 'https://glitchtip.example.com',
    sessionId: $request->cookie('sessionid'),
    csrfToken: $request->cookie('csrftoken'),
    organization: 'acme',
);

$me = $glitchTip->users()->get();
```

The client sends both cookies, the `X-CSRFToken` header and an `Origin` of the instance, so writes pass Django's CSRF checks. Like `using()`, it leaves config and the default client alone. A browser on another origin never sends these cookies, so this does not work for apps on a different domain; use a token there.

### Pagination

Paginated endpoints return a `CursorPage`. It is countable and iterable over the current page, and you can step through pages or stream every item:

```php
$page = GlitchTip::issues()->list(limit: 50);

$page->items;        // list<Issue> on this page
$page->hits;         // total matches reported by GlitchTip (X-Hits)
$page->hasMore();
$next = $page->next();

// Every issue, fetching pages only as they are needed:
GlitchTip::issues()->list(query: 'is:unresolved')
    ->lazy()
    ->filter(fn ($issue) => $issue->userCount > 10)
    ->each(fn ($issue) => /* ... */);
```

### Issues

```php
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;
use WojtJanowski\GlitchTip\Enums\IssueSort;

$issues = GlitchTip::issues()->list(
    query: 'is:unresolved level:error',
    projects: [7],                 // project ids
    environments: ['production'],
    start: now()->subDay(),
    sort: IssueSort::LastSeenDesc,
);

$issues = GlitchTip::issues()->forProject('backend', query: 'is:unresolved');

$issue = GlitchTip::issues()->get(42);
$issue = GlitchTip::issues()->findByShortId('BACKEND-1A');   // needs GlitchTip 7; throws UnsupportedByServerException on 6.x

GlitchTip::issues()->resolve(42);
GlitchTip::issues()->ignore(42);
GlitchTip::issues()->assign(42, 'team:backend');   // or 'user:5', an email, or null

// Combine changes in one request. Only the fields you set are sent.
GlitchTip::issues()->update(42, IssueUpdate::resolvedInRelease('api@1.4.0')
    ->with(IssueUpdate::assignedTo('user:5')));

GlitchTip::issues()->tags(42, key: 'browser');
GlitchTip::issues()->commits(42);
GlitchTip::issues()->userReports(42);
```

Bulk updates and deletes take an `IssueFilter`. GlitchTip applies a bulk call with no filter to **every issue in the organization**, so an empty filter throws unless you build it with `IssueFilter::everything()`:

```php
GlitchTip::issues()->bulkUpdate(IssueFilter::ids([42, 43]), IssueUpdate::ignored());
GlitchTip::issues()->bulkUpdate(new IssueFilter(query: 'is:unresolved', projects: [7]), IssueUpdate::merged());
GlitchTip::issues()->bulkDelete(IssueFilter::ids([42]));

// One project. Without ids, only a status change is allowed, applied to unresolved issues (or $scope).
GlitchTip::issues()->bulkUpdateForProject('backend', IssueFilter::everything(), IssueUpdate::resolved());
```

Issue stats and unmerging:

```php
use WojtJanowski\GlitchTip\Enums\IssueStatsPeriod;

GlitchTip::issues()->stats([42, 43], IssueStatsPeriod::TwoWeeks);   // [timestamp, count] series per issue

$hashes = GlitchTip::issues()->hashes(42);                           // fingerprints grouped into the issue
GlitchTip::issues()->deleteHashes(42, [$hashes->items[1]->id]);      // split them back out
```

### Events

```php
$event = GlitchTip::events()->latest(42);

$event->tags;                    // ['environment' => 'production', ...]
$event->entry('exception');      // the exception entry's data
$event->entry('breadcrumbs');

GlitchTip::events()->forIssue(42)->lazy();
GlitchTip::events()->get(42, $eventId);
GlitchTip::events()->forProject('backend');
GlitchTip::events()->raw(42, $eventId);   // the original Sentry event JSON
```

### Comments

```php
$comment = GlitchTip::comments()->create(42, 'Fixed in #1234, deploying now.');
GlitchTip::comments()->update(42, $comment->id, 'Fixed in #1234, deployed.');
GlitchTip::comments()->list(42);
GlitchTip::comments()->delete(42, $comment->id);
```

### Releases and deploys

```php
GlitchTip::releases()->create('api@1.4.0', projects: ['backend'], ref: $commitSha);
GlitchTip::releases()->createDeploy('api@1.4.0', environment: 'production', url: 'https://api.example.com');
GlitchTip::releases()->finalize('api@1.4.0');      // sets dateReleased to now

GlitchTip::releases()->list();
GlitchTip::releases()->list(project: 'backend');
GlitchTip::releases()->deploys('api@1.4.0');
```

Versions that contain `/` or `@` are encoded for you. Pass `project:` to `get()`, `update()`, `delete()`, `finalize()` and `files()` to work with a release inside one project, or create one there directly:

```php
use WojtJanowski\GlitchTip\Data\Commit;

GlitchTip::releases()->createForProject('backend', 'api@1.4.0');
GlitchTip::releases()->get('api@1.4.0', project: 'backend');

GlitchTip::releases()->setCommits('api@1.4.0', [new Commit(id: $sha, message: 'Fix checkout', authorEmail: 'dev@example.com')]);   // replaces the list
GlitchTip::releases()->commits('api@1.4.0');

GlitchTip::releases()->files('api@1.4.0');
GlitchTip::releases()->deleteFile('api@1.4.0', $fileId);
```

### Source maps and debug files

Uploads work the way sentry-cli does them. `upload()` sends a file in gzipped chunks sized to the server's limits, and an assemble call then turns the chunks into an artifact. Repeat the assemble call until `needsChunks()` is false:

```php
$file = GlitchTip::uploads()->upload(file_get_contents('build/artifact-bundle.zip'));

$status = GlitchTip::uploads()->assembleArtifactBundle($file, projects: ['web'], version: 'web@2.0.0');
$status->state;          // "created", "ok", "not_found" or "error"
$status->needsChunks();

GlitchTip::releases()->assemble('web@2.0.0', $file);                        // legacy release artifacts
GlitchTip::uploads()->assembleDebugFile('ios', $file, 'App.dSYM', $debugId); // dSYM, ELF, PDB, ProGuard
GlitchTip::uploads()->debugFiles('ios');
GlitchTip::uploads()->uploadProguardMappings('android', file_get_contents('mappings.zip'));
```

### Uptime monitors

```php
use WojtJanowski\GlitchTip\Data\MonitorInput;
use WojtJanowski\GlitchTip\Enums\MonitorType;

$monitor = GlitchTip::monitors()->create(new MonitorInput(
    name: 'Homepage',
    type: MonitorType::Get,
    url: 'https://example.com',
    interval: 60,
    expectedStatus: 200,
));

$monitor->isUp;
GlitchTip::monitors()->checks($monitor->id, onlyChanges: true);
GlitchTip::monitors()->list();
GlitchTip::monitors()->delete($monitor->id);
```

Heartbeat monitors expect a ping from your job. `heartbeat()` needs no API token, only the monitor's endpoint id:

```php
GlitchTip::monitors()->heartbeat($monitor->endpointId);

GlitchTip::monitors()->createStatusPage('Acme status', public: true);
GlitchTip::monitors()->statusPages();
```

### Alerts

```php
use WojtJanowski\GlitchTip\Data\AlertInput;

GlitchTip::alerts()->create('backend', new AlertInput(
    name: 'Error spike',
    timespanMinutes: 5,
    quantity: 20,
    recipients: [['recipientType' => 'email']],
));

GlitchTip::alerts()->list('backend');
GlitchTip::alerts()->test('backend', $alert->id);   // sends a test notification; returns a result per recipient
```

### Logs

```php
GlitchTip::logs()->list(levels: ['error'], service: 'api', start: now()->subHour());
GlitchTip::logs()->get($logId);
GlitchTip::logs()->stats(projects: [7], start: now()->subDay())->series;   // ['error' => [...], 'info' => [...]]
GlitchTip::logs()->resources('service');   // known services, environments or hosts, for filters
```

### Performance

```php
use WojtJanowski\GlitchTip\Enums\TransactionGroupSort;

$slowest = GlitchTip::performance()->transactionGroups(projects: [7], sort: TransactionGroupSort::AverageDurationDesc);

GlitchTip::performance()->transactionSpans($group->id);
GlitchTip::performance()->trend($group->id, start: now()->subWeek());
GlitchTip::performance()->spanGroups(projects: [7], op: 'db.sql.query');
GlitchTip::performance()->nPlusOne(projects: [7]);
```

### Server version

`GlitchTip::server()` reads `/api/settings/` and returns the instance's version and enabled features:

```php
$server = GlitchTip::server();

$server->version;                   // "6.2.6"
$server->versionAtLeast('7.0.0');   // pre-releases count, so "7.0.0rc2" passes
$server->hasFeature('logs');        // also 'uptime', 'mcp', 'email'
```

### Instance and credentials

```php
$auth = GlitchTip::instance()->whoami();
$auth->user?->email;
$auth->hasScope('event:write');

GlitchTip::instance()->loginProviders();   // SSO options on the organization's login page; no token needed
GlitchTip::instance()->license();
GlitchTip::instance()->supportLink();
```

### Event volume

```php
use WojtJanowski\GlitchTip\Enums\StatsCategory;
use WojtJanowski\GlitchTip\Enums\StatsInterval;

$series = GlitchTip::stats()->series(now()->subWeek(), now(), StatsCategory::Error, interval: StatsInterval::Day);
$series->toMap();   // ['2026-10-14T00:00:00+00:00' => 120, ...]
```

### Organizations, teams and projects

```php
use WojtJanowski\GlitchTip\Data\ProjectInput;

GlitchTip::organizations()->create('Acme');
GlitchTip::organizations()->update('Acme Inc');
GlitchTip::organizations()->import('https://sentry.example.com', $sourceToken);   // copy projects, teams and members

GlitchTip::teams()->create('platform');
GlitchTip::teams()->addMember('platform', $memberId);
GlitchTip::teams()->addToProject('platform', 'backend');

$project = GlitchTip::projects()->create('platform', new ProjectInput(name: 'Backend', platform: 'php-laravel'));
GlitchTip::projects()->update('backend', new ProjectInput(name: 'Backend API'));

$key = GlitchTip::projects()->createKey('backend', 'Mobile', rateLimitWindow: 60, rateLimitCount: 1000);
$key->dsn;
GlitchTip::projects()->deleteKey('backend', $key->id);

GlitchTip::environments()->forProject('backend');
GlitchTip::environments()->hide('backend', 'staging');
```

The `delete()` methods on organizations, teams and projects remove all of their data. GlitchTip doesn't ask for confirmation.

### Members and invitations

```php
use WojtJanowski\GlitchTip\Enums\MemberRole;

$member = GlitchTip::members()->invite('dev@example.com', MemberRole::Member, teams: ['platform']);
$member->inviteLink;    // share it yourself with sendEmail: false

GlitchTip::members()->update($member->id, MemberRole::Admin, teams: ['platform' => 'admin']);
GlitchTip::members()->makeOwner($member->id);
GlitchTip::members()->forTeam('platform');

// The invitee's side, from the link's member id and token:
GlitchTip::members()->invitation($memberId, $token);   // no token needed
GlitchTip::members()->acceptInvitation($memberId, $token);
```

### Single sign-on

```php
GlitchTip::socialApps()->create('Okta', clientId: $id, clientSecret: $secret, serverUrl: 'https://acme.okta.com');
GlitchTip::socialApps()->list();
```

### Users

GlitchTip lets a token manage only its own user, so these methods default to `'me'`:

```php
use WojtJanowski\GlitchTip\Data\UserOptions;

GlitchTip::users()->get();
GlitchTip::users()->update(new UserOptions(timezone: 'Australia/Sydney', clock24Hours: true), name: 'Wojt');

GlitchTip::users()->addEmail('me@work.example');
GlitchTip::users()->makeEmailPrimary('me@work.example');
GlitchTip::users()->updateNotifications(subscribeByDefault: false);
GlitchTip::projects()->updateAlertPreferences([7 => 1, 9 => 0]);   // per project: -1 default, 0 off, 1 on
```

### Usage and billing

Event usage works on self-hosted instances too. `usage()` needs GlitchTip 6.1.3 or newer, `dailyUsage()` 6.1.4 and `overage()` 6.2.1; older servers answer 404:

```php
$usage = GlitchTip::billing()->usage();      // this period; usage(periodsAgo: 1) for the last
$usage->total;
$usage->transactionEventCount;
$usage->uptimeCheckEventCount;               // a float: GlitchTip weights uptime and log events
GlitchTip::billing()->dailyUsage();
GlitchTip::billing()->overage()->quota;
```

Plans, subscriptions, checkout and the portal need Stripe billing, as on app.glitchtip.com. Without it, `products()` is empty and `subscription()` returns null:

```php
GlitchTip::billing()->subscription()?->status;
GlitchTip::billing()->checkout($priceId);     // Stripe Checkout URL
GlitchTip::billing()->portal();               // Stripe customer portal URL
GlitchTip::billing()->configureOverage(true, capCents: 5000);
```

### Other resources

```php
GlitchTip::organizations()->list();
GlitchTip::projects()->list();
GlitchTip::projects()->keys('backend')->items[0]->dsn;
GlitchTip::teams()->list();
GlitchTip::environments()->list();
GlitchTip::repositories()->create('acme/backend', 'https://github.com/acme/backend');
GlitchTip::wizard()->authorize($hash);   // complete a sentry-wizard handshake
```

## API coverage

`tests/Fixtures/coverage.php` maps every operation in GlitchTip's OpenAPI spec (`tests/Fixtures/openapi.json`) to one of three things:

- **A client call.** The test suite runs it and checks the request uses exactly the operation's HTTP method and path.
- **An alias.** Some operations are duplicate routes, for example the organization-scoped copies of the issue, event and comment endpoints. The client reaches the same data through the other route.
- **An exclusion, with the reason.**

The suite fails if any operation in the spec isn't accounted for. Eight operations are excluded:

| Operations | Why |
|---|---|
| API tokens: list, create, delete | Accept only browser-session login; an API token can't call them |
| Event store, CSP security reports | Event ingest, authenticated by a project DSN. Send events with a Sentry SDK |
| Embedded error page (GET, POST) | The browser crash-report dialog, keyed by DSN |
| Project reprocessing | A documented no-op that exists only so `sentry-cli upload-dif` succeeds |

## Error handling

Every non-2xx response throws `WojtJanowski\GlitchTip\Exceptions\RequestFailedException`. The message includes GlitchTip's `detail`, and validation errors are summarised:

```php
use WojtJanowski\GlitchTip\Exceptions\RequestFailedException;

try {
    GlitchTip::issues()->get(42);
} catch (RequestFailedException $e) {
    $e->status;            // 404
    $e->isNotFound();
    $e->isUnauthorized();
    $e->isRateLimited();
    $e->response;          // the Illuminate\Http\Client\Response
}
```

A missing token or organization throws `MissingConfigurationException`. Calling an endpoint the server is too old for throws `UnsupportedByServerException`. All of these extend `GlitchTipException`.

## Testing your code

The client is built on Laravel's HTTP client, so fake the API with `Http::fake()`:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/api/0/issues/42/' => Http::response(['id' => '42', 'status' => 'resolved', /* ... */]),
]);
```

## Development

```bash
composer test -- tests/Feature/ClientTest.php
composer analyse
composer format
composer refactor
```

### Integration tests

`tests/Integration` runs the client against a real GlitchTip organization. It creates teams, projects, keys, events, releases, uploads, monitors, alerts and SSO apps, all named `it-<timestamp>-<random>`. It deletes each one at the end, even when a test fails, and any cleanup that fails also fails the test. Without these variables, the suite is skipped:

```bash
GLITCHTIP_INTEGRATION_URL=https://glitchtip.example.com \
GLITCHTIP_INTEGRATION_TOKEN=... \
GLITCHTIP_INTEGRATION_ORGANIZATION=acme \
composer test -- tests/Integration
```

The token needs write scopes. The suite briefly changes the token user's display options and restores them. It doesn't send notifications or emails. Set `GLITCHTIP_INTEGRATION_UNDELETABLE=1` to also create a status page and a repository. Neither the API nor GlitchTip's normal interface can delete those, so the run prints their names. Remove them as a superuser in Django's admin, at `/admin/uptime/statuspage/` and `/admin/sourcecode/repository/`.

## License

MIT. See [LICENSE.md](LICENSE.md).
