<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Tests\Unit\Lock;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheGuard\Lock\FlushLock;

final class FlushLockTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']);
        parent::tearDown();
    }

    private function initializeEnvironment(string $context, bool $cli): void
    {
        Environment::initialize(
            new ApplicationContext($context),
            $cli,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX',
        );
    }

    #[Test]
    public function locksSystemGroupInProductionWebContext(): void
    {
        $this->initializeEnvironment('Production', false);
        self::assertTrue((new FlushLock())->isGroupLocked('system'));
    }

    #[Test]
    public function doesNotLockPagesGroupByDefault(): void
    {
        $this->initializeEnvironment('Production', false);
        self::assertFalse((new FlushLock())->isGroupLocked('pages'));
    }

    #[Test]
    public function doesNotLockOnCommandLine(): void
    {
        $this->initializeEnvironment('Production', true);
        self::assertFalse((new FlushLock())->isGroupLocked('system'));
    }

    #[Test]
    public function doesNotLockInDevelopmentContext(): void
    {
        $this->initializeEnvironment('Development', false);
        self::assertFalse((new FlushLock())->isGroupLocked('system'));
    }

    #[Test]
    public function locksInProductionSubContext(): void
    {
        $this->initializeEnvironment('Production/Staging', false);
        self::assertTrue((new FlushLock())->isGroupLocked('system'));
    }

    #[Test]
    public function locksConfiguredGroups(): void
    {
        $this->initializeEnvironment('Production', false);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedGroups'] = 'system, pages';
        $flushLock = new FlushLock();
        self::assertTrue($flushLock->isGroupLocked('pages'));
        self::assertTrue($flushLock->isGroupLocked('system'));
    }

    #[Test]
    public function locksConfiguredContexts(): void
    {
        $this->initializeEnvironment('Testing', false);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedContexts'] = 'Production, Testing';
        self::assertTrue((new FlushLock())->isGroupLocked('system'));
    }

    #[Test]
    public function blocksFullOpcacheResetWhenSystemGroupIsLocked(): void
    {
        $this->initializeEnvironment('Production', false);
        self::assertTrue((new FlushLock())->isFullOpcacheResetBlocked(null));
    }

    #[Test]
    public function allowsTargetedOpcacheInvalidationEvenWhenLocked(): void
    {
        $this->initializeEnvironment('Production', false);
        self::assertFalse((new FlushLock())->isFullOpcacheResetBlocked('/some/file.php'));
    }

    #[Test]
    public function allowsFullOpcacheResetOnCommandLine(): void
    {
        $this->initializeEnvironment('Production', true);
        self::assertFalse((new FlushLock())->isFullOpcacheResetBlocked(null));
    }

    #[Test]
    public function reportsActiveLockWhenAnyGroupIsConfigured(): void
    {
        $this->initializeEnvironment('Production', false);
        self::assertTrue((new FlushLock())->isAnyGroupLocked());
    }

    #[Test]
    public function reportsNoActiveLockWithEmptyGroupConfiguration(): void
    {
        $this->initializeEnvironment('Production', false);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedGroups'] = '';
        self::assertFalse((new FlushLock())->isAnyGroupLocked());
    }

    #[Test]
    public function doesNotLockSiblingSubContextWithSharedPrefix(): void
    {
        $this->initializeEnvironment('Production/StagingFoo', false);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_guard']['lockedContexts'] = 'Production/Staging';
        self::assertFalse((new FlushLock())->isGroupLocked('system'));
    }
}
