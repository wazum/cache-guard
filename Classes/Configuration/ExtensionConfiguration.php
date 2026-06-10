<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Configuration;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration as CoreExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class ExtensionConfiguration
{
    private const EXTENSION_KEY = 'cache_flush_lock';

    public function __construct(
        private CoreExtensionConfiguration $coreExtensionConfiguration = new CoreExtensionConfiguration(),
    ) {}

    /** @return list<string> */
    public function getLockedGroups(): array
    {
        return GeneralUtility::trimExplode(',', $this->stringValue('lockedGroups', 'system'), true);
    }

    /** @return list<string> */
    public function getLockedContexts(): array
    {
        return GeneralUtility::trimExplode(',', $this->stringValue('lockedContexts', 'Production'), true);
    }

    private function stringValue(string $path, string $default): string
    {
        if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? [], self::EXTENSION_KEY . '/' . $path)) {
            return $default;
        }

        try {
            return (string) $this->coreExtensionConfiguration->get(self::EXTENSION_KEY, $path);
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return $default;
        }
    }
}
