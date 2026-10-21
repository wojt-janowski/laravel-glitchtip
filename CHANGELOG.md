# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Typed client for the GlitchTip API: organizations, projects and keys, teams,
  environments, issues (list, triage, bulk update and delete, tags, commits,
  user reports), events, comments, releases and deploys, uptime monitors,
  project alerts, logs and performance (transaction groups, spans, N+1
  patterns, trends).
- Cursor pagination over GlitchTip's `Link` header, with lazy iteration
  across pages.
- Retries for idempotent requests on connection failures, 429 and 5xx gateway
  responses.
