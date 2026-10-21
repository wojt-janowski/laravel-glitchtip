# laravel-glitchtip

A typed Laravel client for the [GlitchTip](https://glitchtip.com) error-tracking API. It covers issues, events, comments, releases and deploys, uptime monitors, alerts, logs and performance data, and works with the hosted service or a self-hosted install.

## Features

- Typed, readonly DTOs and enums for every response, not raw arrays
- Cursor pagination over GlitchTip's `Link` header, with `lazy()` iteration across pages
- Issue triage: resolve, ignore, assign, resolve in a release, merge, and bulk update or delete
- Release and deploy tracking for CI and deploy scripts
- Uptime monitors, project alerts, logs, transaction groups, span groups and N+1 detection
- Retries on connection failures, 429 and 5xx gateway responses for idempotent requests only, so a POST is never duplicated
- A single `RequestFailedException` that carries GlitchTip's error detail
- Testable with `Http::fake()`

## Requirements

- PHP 8.5+
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
$issue = GlitchTip::issues()->findByShortId('BACKEND-1A');

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

Versions that contain `/` or `@` are encoded for you.

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
```

### Logs

```php
GlitchTip::logs()->list(levels: ['error'], service: 'api', start: now()->subHour());
GlitchTip::logs()->get($logId);
GlitchTip::logs()->stats(projects: [7], start: now()->subDay())->series;   // ['error' => [...], 'info' => [...]]
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

### Other resources

```php
GlitchTip::organizations()->list();
GlitchTip::organizations()->get();
GlitchTip::projects()->list();
GlitchTip::projects()->get('backend');
GlitchTip::projects()->keys('backend')->items[0]->dsn;
GlitchTip::teams()->list();
GlitchTip::environments()->list();
```

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

A missing token or organization throws `MissingConfigurationException`. Both exceptions extend `GlitchTipException`.

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

## License

MIT. See [LICENSE.md](LICENSE.md).
