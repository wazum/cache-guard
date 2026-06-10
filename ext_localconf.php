<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use Wazum\CacheGuard\Cache\LockableCacheManager;
use Wazum\CacheGuard\Service\LockableOpcodeCacheService;
use Wazum\CacheGuard\Service\LockableOpcodeCacheServiceV12;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][CacheManager::class] = [
    'className' => LockableCacheManager::class,
];

// OpcodeCacheService is readonly on v13+ but a plain class on v12; PHP forbids one
// subclass from extending both, so select the matching implementation by version.
// ::class is a compile-time string and does not autoload the wrong-version class.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OpcodeCacheService::class] = [
    'className' => (new Typo3Version())->getMajorVersion() >= 13
        ? LockableOpcodeCacheService::class
        : LockableOpcodeCacheServiceV12::class,
];

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['cache_guard'] = 'EXT:cache_guard/Resources/Public/Css/backend.css';
