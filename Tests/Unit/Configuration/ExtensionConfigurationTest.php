<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheGuard\Configuration\ExtensionConfiguration;

final class ExtensionConfigurationTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']);
        parent::tearDown();
    }

    #[Test]
    public function returnsDefaultLockedGroupsWhenNothingIsConfigured(): void
    {
        self::assertSame(['system'], (new ExtensionConfiguration())->getLockedGroups());
    }

    #[Test]
    public function returnsConfiguredLockedGroups(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedGroups'] = 'system, pages';
        self::assertSame(['system', 'pages'], (new ExtensionConfiguration())->getLockedGroups());
    }

    #[Test]
    public function returnsEmptyLockedGroupsForEmptyValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedGroups'] = '';
        self::assertSame([], (new ExtensionConfiguration())->getLockedGroups());
    }

    #[Test]
    public function returnsDefaultLockedContextsWhenNothingIsConfigured(): void
    {
        self::assertSame(['Production'], (new ExtensionConfiguration())->getLockedContexts());
    }

    #[Test]
    public function returnsConfiguredLockedContexts(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedContexts'] = 'Production, Testing';
        self::assertSame(['Production', 'Testing'], (new ExtensionConfiguration())->getLockedContexts());
    }
}
