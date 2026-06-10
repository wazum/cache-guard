<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheFlushLock\EventListener\RemoveLockedClearCacheActions;
use Wazum\CacheFlushLock\Lock\FlushLock;

final class RemoveLockedClearCacheActionsTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    #[Test]
    public function keepsAllActionsInUnlockedContext(): void
    {
        $this->initializeEnvironment('Development', false);
        $event = $this->createEventWithPagesAndAllActions();

        (new RemoveLockedClearCacheActions(new FlushLock()))($event);

        self::assertSame(['pages', 'all'], array_column($event->getCacheActions(), 'id'));
        self::assertSame(['pages', 'all'], $event->getCacheActionIdentifiers());
    }

    #[Test]
    public function removesFlushAllActionInLockedContext(): void
    {
        $this->initializeEnvironment('Production', false);
        $event = $this->createEventWithPagesAndAllActions();

        (new RemoveLockedClearCacheActions(new FlushLock()))($event);

        self::assertNotContains('all', array_column($event->getCacheActions(), 'id'));
        self::assertSame(['pages'], $event->getCacheActionIdentifiers());
    }

    #[Test]
    public function addsInertInformationRowInLockedContext(): void
    {
        $this->initializeEnvironment('Production', false);
        $event = $this->createEventWithPagesAndAllActions();

        (new RemoveLockedClearCacheActions(new FlushLock()))($event);

        $actions = $event->getCacheActions();
        self::assertSame(['pages', 'cacheFlushLockInformation'], array_column($actions, 'id'));

        $informationRow = end($actions);
        self::assertArrayNotHasKey('endpoint', $informationRow);
        self::assertSame('info', $informationRow['severity']);
        self::assertStringContainsString('locallang.xlf:toolbar.systemCachesLocked', $informationRow['title']);
    }

    private function createEventWithPagesAndAllActions(): ModifyClearCacheActionsEvent
    {
        return new ModifyClearCacheActionsEvent(
            [
                ['id' => 'pages', 'title' => 'Pages', 'description' => '', 'iconIdentifier' => 'actions-system-cache-clear-impact-low'],
                ['id' => 'all', 'title' => 'All', 'description' => '', 'iconIdentifier' => 'actions-system-cache-clear-impact-high'],
            ],
            ['pages', 'all'],
        );
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
}
