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
  `forOrganization()` returns a re-scoped copy.
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
- Release versions can contain `/` and `@`, so every path segment is
  `rawurlencode`d.

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
