# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-06-23

Initial release.

### Added

- Lock configured cache groups (default: `system`) against backend flushes in
  configured application contexts (default: `Production`, matched as a prefix so
  `Production/Staging` is included).
- Block the full opcache reset behind "Flush all caches" while the lock is
  active, on TYPO3 13.4+; targeted single-file opcache invalidation keeps working.
- Replace the "Flush all caches" toolbar entry with a disabled notice while the
  lock is active; the dropdown and "Flush frontend caches" stay in place.
- `cache:flush --cache <identifiers>` to flush individual named caches, with
  all-or-nothing validation and an interactive picker when run without a value
  at a terminal.
- Support for TYPO3 12.4, 13.4 and 14.3 on PHP 8.2–8.5 (12.4 runs on PHP 8.2–8.3).

[1.0.0]: https://github.com/wazum/cache-guard/releases/tag/1.0.0
