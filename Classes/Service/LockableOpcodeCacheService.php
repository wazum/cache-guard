<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Service;

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use Wazum\CacheFlushLock\Lock\FlushLock;

final readonly class LockableOpcodeCacheService extends OpcodeCacheService
{
    public function clearAllActive(?string $fileAbsPath = null): void
    {
        if ((new FlushLock())->isFullOpcacheResetBlocked($fileAbsPath)) {
            return;
        }
        parent::clearAllActive($fileAbsPath);
    }
}
