<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use Wazum\CacheGuard\Cache\LockableCacheManager;
use Wazum\CacheGuard\Service\LockableOpcodeCacheService;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CacheManager::class] = [
    'className' => LockableCacheManager::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OpcodeCacheService::class] = [
    'className' => LockableOpcodeCacheService::class,
];

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['cache_guard'] = 'EXT:cache_guard/Resources/Public/Css/backend.css';
