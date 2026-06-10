# Cache Flush Lock

Prevents backend cache flushes of protected cache groups in production-like TYPO3 contexts. Deployment warms the system caches; this extension makes sure no backend user wipes them between deployments.

## What it does

- Blocks flushing of configured cache groups (default: `system`) in configured application contexts (default: `Production`, matched as prefix, so `Production/Staging` is included).
- Blocks the full opcache reset triggered by "Flush all caches"; targeted single-file opcache invalidation keeps working.
- Hides the "Flush all caches" toolbar action while the lock is active.
- "Flush frontend caches" (pages) keeps working for editors.

## What it does not block

- CLI: `vendor/bin/typo3 cache:flush` always works — deployments are unaffected.
- The Install Tool / Maintenance "Flush cache" and the DI container cache (system-maintainer territory).
- Tag-based invalidation (`flushCachesByTag`/`flushCachesByTags`) — targeted invalidation is not a bulk clear.

## Installation

```bash
composer require wazum/cache-flush-lock
```

Supports TYPO3 13.4 and 14.3, PHP 8.2+.

## Configuration

Admin Tools → Settings → Extension Configuration → cache_flush_lock:

| Option | Default | Description |
| --- | --- | --- |
| `lockedGroups` | `system` | Comma-separated cache groups that must not be flushed from the backend |
| `lockedContexts` | `Production` | Comma-separated application context prefixes in which the lock is active |

## Defense in depth (optional)

Hide the "Flush all caches" entry per user/group via User TSconfig (UI only, the lock above is the actual enforcement):

```
options.clearCache.all = 0
```

