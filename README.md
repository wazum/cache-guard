# Cache Guard

[![Tests](https://github.com/wazum/cache-guard/actions/workflows/ci.yml/badge.svg)](https://github.com/wazum/cache-guard/actions)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4%20|%208.5-blue.svg)](https://www.php.net/)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4%20|%2014.3-orange.svg)](https://typo3.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

Deliberate cache control for TYPO3: **protect** the warmed system caches from destructive backend flushes between deployments, and **flush precisely** the caches you mean to. Editors keep clearing page caches, deployments keep flushing everything, and you can clear a single cache (e.g. `fluid_template`) instead of wiping the whole system group — while the bulk flush of warmed system caches stays locked in production.

## Installation

```bash
composer require wazum/cache-guard
```

No setup needed — the defaults lock the `system` cache group in `Production` contexts.

## Locking system-cache flushes

Between deployments the warmed system caches (compiled PHP code, Fluid templates, l10n, …) should not be wiped by a stray backend click. Cache Guard prevents that in production-like contexts, while everyday page-cache clearing keeps working.

### What it blocks

- Flushing of configured cache groups (default: `system`) in configured application contexts (default: `Production`, matched as prefix, so `Production/Staging` is included).
- The full opcache reset triggered by "Flush all caches"; targeted single-file opcache invalidation keeps working.
- It replaces the "Flush all caches" entry in the clear-cache toolbar with a disabled notice while the lock is active; the dropdown and "Flush frontend caches" stay in place.

> [!NOTE]
> Intercepting the opcache reset requires TYPO3 13.4+ — on 12.4 the cache-group lock applies, but the opcache reset is not intercepted.

![Clear-cache dropdown with the system cache flush locked](Documentation/Images/clear-cache-dropdown.png)

### What it leaves alone

- "Flush frontend caches" (pages) keeps working for editors.
- CLI: `vendor/bin/typo3 cache:flush` always works — deployments are unaffected.
- The Install Tool / Maintenance "Flush cache" and the DI container cache (system-maintainer territory).
- Tag-based invalidation (`flushCachesByTag`/`flushCachesByTags`) — targeted invalidation is not a bulk clear.

### Configuration

Admin Tools → Settings → Extension Configuration → cache_guard:

| Option | Default | Description |
| --- | --- | --- |
| `lockedGroups` | `system` | Comma-separated cache groups that must not be flushed from the backend |
| `lockedContexts` | `Production` | Comma-separated application context prefixes in which the lock is active |

### Defense in depth (optional)

Hide the "Flush all caches" entry per user/group via User TSconfig (UI only, the lock above is the actual enforcement):

```
options.clearCache.all = 0
```

## Flushing individual caches (CLI)

Clear only specific caches without wiping a whole group — e.g. recompile Fluid templates after a deployment without touching the warmed PHP-code, l10n or DI caches:

```bash
vendor/bin/typo3 cache:flush --cache fluid_template
vendor/bin/typo3 cache:flush --cache fluid_template,l10n
```

All identifiers must be valid or nothing is flushed. The dependency injection cache is not flushable this way — use `cache:flush --group di`.

Run the option without a value (or with an unknown name) at a terminal to pick interactively:

```bash
vendor/bin/typo3 cache:flush --cache
```

![Interactive cache picker](Documentation/Images/cache-flush-picker.gif)

In CI and deployment, pass `--no-interaction` (`-n`) so a missing or unknown identifier fails with a clear error instead of opening the picker:

```bash
vendor/bin/typo3 cache:flush --cache fluid_template --no-interaction
```

## Requirements

- TYPO3 12.4, 13.4 or 14.3
- PHP 8.2, 8.3, 8.4 or 8.5 (TYPO3 12.4 runs on 8.2–8.3)

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
