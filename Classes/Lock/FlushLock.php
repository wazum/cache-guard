<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Lock;

use TYPO3\CMS\Core\Core\Environment;
use Wazum\CacheFlushLock\Configuration\ExtensionConfiguration;

final readonly class FlushLock
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration = new ExtensionConfiguration(),
    ) {}

    public function isGroupLocked(string $groupIdentifier): bool
    {
        return $this->isLockActive() && in_array($groupIdentifier, $this->extensionConfiguration->getLockedGroups(), true);
    }

    public function isAnyGroupLocked(): bool
    {
        return $this->isLockActive() && $this->extensionConfiguration->getLockedGroups() !== [];
    }

    public function isFullOpcacheResetBlocked(?string $fileAbsPath): bool
    {
        return $fileAbsPath === null && $this->isGroupLocked('system');
    }

    private function isLockActive(): bool
    {
        return !Environment::isCli() && $this->isContextLocked();
    }

    private function isContextLocked(): bool
    {
        $currentContext = (string) Environment::getContext();
        foreach ($this->extensionConfiguration->getLockedContexts() as $lockedContext) {
            if ($currentContext === $lockedContext || str_starts_with($currentContext, $lockedContext . '/')) {
                return true;
            }
        }

        return false;
    }
}
