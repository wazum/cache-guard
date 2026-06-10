<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Service;

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use Wazum\CacheGuard\Lock\FlushLock;

final class LockableOpcodeCacheServiceV12 extends OpcodeCacheService
{
    public function clearAllActive(?string $fileAbsPath = null): void
    {
        if ((new FlushLock())->isFullOpcacheResetBlocked($fileAbsPath)) {
            return;
        }
        parent::clearAllActive($fileAbsPath);
    }
}
