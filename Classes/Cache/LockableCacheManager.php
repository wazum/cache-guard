<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Cache;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use Wazum\CacheGuard\Lock\FlushLock;

final class LockableCacheManager extends CacheManager
{
    public function flushCaches(): void
    {
        $this->createAllCaches();
        $protectedCacheIdentifiers = $this->protectedCacheIdentifiers();
        foreach ($this->caches as $cacheIdentifier => $cache) {
            if (!in_array($cacheIdentifier, $protectedCacheIdentifiers, true)) {
                $cache->flush();
            }
        }
    }

    /**
     * @param string $groupIdentifier
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroup($groupIdentifier): void
    {
        if ((new FlushLock())->isGroupLocked($groupIdentifier)) {
            return;
        }

        parent::flushCachesInGroup($groupIdentifier);
    }

    /**
     * @param string $groupIdentifier
     * @param string $tag
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTag($groupIdentifier, $tag): void
    {
        if ((new FlushLock())->isGroupLocked($groupIdentifier)) {
            return;
        }
        parent::flushCachesInGroupByTag($groupIdentifier, $tag);
    }

    /**
     * @param string $groupIdentifier
     * @param array<int|string, string> $tags
     * @throws NoSuchCacheGroupException
     */
    public function flushCachesInGroupByTags($groupIdentifier, array $tags): void
    {
        if ((new FlushLock())->isGroupLocked($groupIdentifier)) {
            return;
        }
        parent::flushCachesInGroupByTags($groupIdentifier, $tags);
    }

    /** @return list<string> */
    private function protectedCacheIdentifiers(): array
    {
        $flushLock = new FlushLock();
        $protectedCacheIdentifiers = [];
        foreach ($this->cacheGroups as $groupIdentifier => $cacheIdentifiers) {
            if ($flushLock->isGroupLocked($groupIdentifier)) {
                $protectedCacheIdentifiers = [...$protectedCacheIdentifiers, ...array_values($cacheIdentifiers)];
            }
        }
        return array_values(array_unique($protectedCacheIdentifiers));
    }
}
