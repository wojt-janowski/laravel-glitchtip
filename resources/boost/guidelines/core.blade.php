## wojt-janowski/laravel-glitchtip

A typed Laravel client for the GlitchTip error-tracking API: issues, events, comments, releases and deploys, uptime monitors, alerts, logs and performance data.

### Core Classes

| Class | Purpose |
|---|---|
| `WojtJanowski\GlitchTip\Facades\GlitchTip` | Facade for the client singleton |
| `WojtJanowski\GlitchTip\GlitchTip` | Entry point, with one accessor per API area |
| `WojtJanowski\GlitchTip\Support\CursorPage` | One page of results; `lazy()` walks every page |
| `WojtJanowski\GlitchTip\Data\IssueUpdate` | Partial issue update (status, assignee, merge) |
| `WojtJanowski\GlitchTip\Data\IssueFilter` | Target of a bulk update or delete |
| `WojtJanowski\GlitchTip\Exceptions\RequestFailedException` | Any non-2xx response |

### Features

- Listing returns a `CursorPage`. Iterate one page directly, or call `lazy()` to fetch later pages on demand:

@verbatim
<code-snippet name="List and paginate issues" lang="php">
use WojtJanowski\GlitchTip\Enums\IssueSort;
use WojtJanowski\GlitchTip\Facades\GlitchTip;

$page = GlitchTip::issues()->list(query: 'is:unresolved level:error', projects: [7], sort: IssueSort::LastSeenDesc, limit: 50);

foreach ($page->lazy() as $issue) {
    // $issue is WojtJanowski\GlitchTip\Data\Issue
}
</code-snippet>
@endverbatim

- Triage with `IssueUpdate`. Only the fields you set are sent:

@verbatim
<code-snippet name="Triage issues" lang="php">
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;

GlitchTip::issues()->resolve(42);
GlitchTip::issues()->update(42, IssueUpdate::resolvedInRelease('1.4.0')->with(IssueUpdate::assignedTo('team:backend')));
GlitchTip::issues()->bulkUpdate(IssueFilter::ids([42, 43]), IssueUpdate::ignored());
</code-snippet>
@endverbatim

- Releases and deploys, e.g. from a deploy script:

@verbatim
<code-snippet name="Record a release and deploy" lang="php">
GlitchTip::releases()->create('api@1.4.0', projects: ['backend'], ref: $sha);
GlitchTip::releases()->createDeploy('api@1.4.0', environment: 'production');
GlitchTip::releases()->finalize('api@1.4.0');
</code-snippet>
@endverbatim

### Configuration

Set `GLITCHTIP_TOKEN`, `GLITCHTIP_ORGANIZATION` and, for self-hosted installs, `GLITCHTIP_URL`. Publish with `php artisan vendor:publish --tag=glitchtip-config`.

### Conventions

- Organization-scoped calls use the configured slug. Use `GlitchTip::forOrganization('slug')` for another organization.
- Projects are referenced by slug in paths, but by numeric id in `projects:` filter arrays.
- Never build an empty `IssueFilter` for bulk calls. GlitchTip would apply the call to every issue, so the class refuses it unless you use `IssueFilter::everything()`.
- In tests, fake the API with `Http::fake()`. The client uses Laravel's HTTP client.
