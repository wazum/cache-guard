<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Tests\Unit\Cache\Fixtures;

use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;

final class FlushRecordingBackend extends TransientMemoryBackend
{
    /** @var list<string> */
    public static array $flushedCacheIdentifiers = [];

    public function flush(): void
    {
        self::$flushedCacheIdentifiers[] = $this->cacheIdentifier;
        parent::flush();
    }
}
