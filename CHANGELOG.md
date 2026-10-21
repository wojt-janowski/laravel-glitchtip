# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `GlitchTip::usingSession($url, $sessionId, $csrfToken, $organization)`
  returns a client that acts as a user signed in to GlitchTip in the
  browser, for apps served from the instance's origin. It sends the
  `sessionid` and `csrftoken` cookies plus the `X-CSRFToken`, `Origin` and
  `Referer` headers Django's CSRF checks need.

### Fixed

- `stats()->series(interval: StatsInterval::Day)` returned only the first
  hour of each day, because GlitchTip 6.2's stats_v2 does. Daily series are
  now added up from hourly buckets.

### Changed

- Allow Guzzle 8, which current Laravel 13 releases install.

## [1.0.0-RC3] - 2026-10-21

### Added

- The rest of GlitchTip's management API, so every operation in its OpenAPI
  spec is either implemented or excluded with a stated reason:
  - `instance()`: whoami with token scopes, login providers, license and
    support link.
  - `members()`: list, invite, update roles and teams, remove, transfer
    ownership, and look up or accept invitations.
  - `socialApps()` (organization SSO), `users()` (profile, emails,
    notifications, recovery codes), `billing()` (Stripe plans, usage,
    checkout, portal, overage), `repositories()`, `stats()` (event volume
    over time) and `wizard()` (sentry-wizard handshake).
  - `uploads()`: chunked uploads plus assembly of artifact bundles, release
    artifacts and debug files, and ProGuard mapping uploads.
  - Organizations: create, update, delete, import. Projects: create, update,
    delete, client key management, per-project alert preferences. Teams:
    create, update, delete, membership and project access. Environments:
    per-project listing and hiding.
  - Issues: per-project bulk update, stats, and hash listing and deletion
    (unmerge). Releases: project-scoped create, get, update and delete,
    files, commits, assembly. Monitors: heartbeats and status pages. Alerts:
    test notifications. Logs: resources.
- `tests/Fixtures/coverage.php` with a test that maps every operation in the
  spec to a client call, an alias or a documented exclusion, and checks each
  call's method and path.

### Changed

- `GlitchTip::server()` and the other endpoints that need no token
  (heartbeats, invitation lookups, login providers) now work without one.

### Deprecated

- `Resources\Server`, in favour of `Resources\Instance`
  (`GlitchTip::instance()->settings()`). `GlitchTip::server()` is unchanged.

## [1.0.0-RC2] - 2026-10-21

### Added

- `GlitchTip::using($url, $token, $organization)` returns a separate client
  for another account, so an app can read several accounts side by side
  without touching config or the default client.
- `GlitchTip::url()` returns the base URL a client talks to.

## [1.0.0-RC1] - 2026-10-21

### Added

- Typed client for the GlitchTip API: organizations, projects and keys, teams,
  environments, issues (list, triage, bulk update and delete, tags, commits,
  user reports), events, comments, releases and deploys, uptime monitors,
  project alerts, logs and performance (transaction groups, spans, N+1
  patterns, trends).
- Cursor pagination over GlitchTip's `Link` header, with lazy iteration
  across pages.
- `GlitchTip::server()` reports the instance version and enabled features.
  Short id lookup on a server older than GlitchTip 7 throws
  `UnsupportedByServerException` instead of a bare 404.
- Retries for idempotent requests on connection failures, 429 and 5xx gateway
  responses.

[Unreleased]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC3...HEAD
[1.0.0-RC3]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC2...v1.0.0-RC3
[1.0.0-RC2]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC1...v1.0.0-RC2
[1.0.0-RC1]: https://github.com/wojt-janowski/laravel-glitchtip/releases/tag/v1.0.0-RC1
