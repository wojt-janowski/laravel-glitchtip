# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.0] - 2026-10-21

### Added

- An opt-in live integration suite (`tests/Integration`) that runs create,
  update and delete scenarios against a real GlitchTip organization and
  cleans up after itself.
- `GlitchTip::usingSession($url, $sessionId, $csrfToken, $organization)`
  returns a client that acts as a user signed in to GlitchTip in the
  browser, for apps served from the instance's origin. It sends the
  `sessionid` and `csrftoken` cookies plus the `X-CSRFToken`, `Origin` and
  `Referer` headers Django's CSRF checks need.

### Fixed

- `monitors()->heartbeat()` always reported the monitor as down, because
  that endpoint answers in snake_case (`is_up`) unlike the rest of the API.
- `monitors()->update()` failed with a 500 for monitors without a URL, such
  as heartbeats. A missing URL is now sent as an empty string.
- `users()->update()` without a name failed with a 500, because GlitchTip
  writes a missing name as null. The current name is now sent back.
- `socialApps()->update()` without a `serverUrl` failed with "Server URL is
  required". It now keeps the app's current issuer URL, as documented.
- `projects()->updateKey()` is now documented as a no-op on GlitchTip 6.2,
  which returns the key unchanged.
- The billing docs said self-hosted instances answer 404. Usage, daily usage
  and overage status work on self-hosted GlitchTip from 6.1.3, 6.1.4 and 6.2.1
  respectively; only the Stripe plan, subscription, checkout and portal calls
  need Stripe billing.
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

[Unreleased]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC3...v1.0.0
[1.0.0-RC3]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC2...v1.0.0-RC3
[1.0.0-RC2]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC1...v1.0.0-RC2
[1.0.0-RC1]: https://github.com/wojt-janowski/laravel-glitchtip/releases/tag/v1.0.0-RC1
