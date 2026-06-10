<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use Wazum\CacheFlushLock\Cache\LockableCacheManager;
use Wazum\CacheFlushLock\Service\LockableOpcodeCacheService;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CacheManager::class] = [
    'className' => LockableCacheManager::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OpcodeCacheService::class] = [
    'className' => LockableOpcodeCacheService::class,
];
