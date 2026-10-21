# wojt-janowski/laravel-glitchtip

## Quick Reference

- **Type**: Laravel package (library), a typed client for the GlitchTip REST API
- **PHP**: 8.5+ with strict_types
- **Laravel**: 13+
- **Namespace**: `WojtJanowski\GlitchTip\`
- **API reference**: https://app.glitchtip.com/api/docs (OpenAPI at `/api/openapi.json`)

## Architecture

- `GlitchTip` is the entry point (singleton, also the `GlitchTip` facade). It
  hands out one resource class per API area and carries the organization slug;
  `forOrganization()` returns a re-scoped copy, and `using($url, $token,
  $organization)` returns a client for another account. Clients are immutable:
  never add a way to change the singleton's server or token at runtime,
  because apps read several accounts in one request.
- `Support\Connector` wraps Laravel's HTTP client: base URL, bearer token,
  JSON, timeouts, retries, and `RequestFailedException` on any non-2xx.
  POSTs are never retried.
- `Resources\*` map one method to one endpoint and return DTOs. Paginated
  endpoints return `Support\CursorPage<T>`; unpaginated lists return `list<T>`.
- `Data\*` are `final readonly` DTOs. Response DTOs build through
  `fromArray()` using `Support\Attributes`, which never throws on missing or
  mistyped keys; input objects (`IssueUpdate`, `IssueFilter`, `MonitorInput`,
  `AlertInput`) expose `toArray()`/`toQuery()`.

## API Quirks Worth Knowing

- Query lists must be repeated keys (`project=1&project=2`), not PHP brackets.
  Always go through `Support\Query::build()`.
- Pagination is Sentry-style: a `Link` header with `rel`, `results` and
  `cursor` attributes, plus `X-Hits`.
- Issue updates apply only the fields sent, so `assignedTo: null` unassigns
  while an absent key leaves the assignee alone. `IssueUpdate` tracks which
  fields were set.
- Bulk issue update and delete with no filter hit every issue in the
  organization. `IssueFilter` refuses an empty filter unless built with
  `IssueFilter::everything()`.
- Issue `count` is a string in the API, so DTOs cast it.
- `stats_v2` with `interval=1d` returns only the first hour of each day
  (seen on 6.2.6). `Stats::series()` asks for hours and sums them per UTC day.
- Release versions can contain `/` and `@`, so every path segment is
  `rawurlencode`d.
- Multipart uploads must not call `asJson()`: its Content-Type header
  overrides the multipart boundary. `Connector::postFiles()` handles this.
- Chunk uploads go to `file_gzip`, each part gzipped and named by the SHA-1
  of its uncompressed content; GlitchTip verifies the name.
- A few endpoints need no token (`/api/settings/`, heartbeats, invitation
  lookups, the sentry-wizard hash endpoints); use `getPublic()`,
  `postPublic()` or `deletePublic()` for them.
- User endpoints only act on the token's own user; default to `'me'`.

## API Coverage

`tests/Fixtures/openapi.json` is GlitchTip's spec, and
`tests/Fixtures/coverage.php` maps every operation in it to a client call, an
alias or an exclusion with a reason. `CoverageTest` fails when an operation is
unmapped and checks each call's HTTP method and path. When GlitchTip adds
endpoints, run `composer spec`, then implement or exclude each new operation.
Exclude only what an API token cannot use or what is not management (DSN
ingest, browser widgets, no-ops), and give the reason.

## Conventions

- `declare(strict_types=1)` in all PHP files
- `final` classes (`final readonly` for DTOs and resources) unless extension is intended
- All parameters and return types fully typed; generics documented for PHPStan
- Public constructors and resource methods are consumer contracts: append new
  optional parameters at the end, never reorder
- No `env()` outside `config/glitchtip.php`

## Commands

- `composer test -- tests/Feature/ClientTest.php`: run the focused Pest tests while iterating
- `composer analyse`: PHPStan level 6
- `composer format`: Pint
- `composer refactor`: Rector

Do not run the complete Pest suite for routine local reassurance. Run focused
and neighbouring test files locally and leave the complete suite to CI.

## Delivery

Use one PR per coherent outcome, with small logical commits. Record changes
under `## [Unreleased]` in `CHANGELOG.md`, and tag releases as `vX.Y.Z` once
CI is green.
