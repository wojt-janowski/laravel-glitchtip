# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/wojt-janowski/laravel-glitchtip/compare/v1.0.0-RC1...HEAD
[1.0.0-RC1]: https://github.com/wojt-janowski/laravel-glitchtip/releases/tag/v1.0.0-RC1
