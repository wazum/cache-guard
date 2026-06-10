<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Cache;

use TYPO3\CMS\Core\Cache\CacheManager;

final readonly class NamedCacheFlusher
{
    /**
     * @param list<string> $identifiers
     * @return list<string> unknown identifiers; when non-empty nothing is flushed
     */
    public function flush(CacheManager $cacheManager, array $identifiers): array
    {
        $unknown = array_values(array_filter(
            $identifiers,
            static fn(string $identifier): bool => !$cacheManager->hasCache($identifier),
        ));
        if ($unknown !== []) {
            return $unknown;
        }
        foreach ($identifiers as $identifier) {
            $cacheManager->getCache($identifier)->flush();
        }
        return [];
    }
}
