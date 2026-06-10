<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheFlushLock\Service\LockableOpcodeCacheService;

final class LockableOpcodeCacheServiceTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    #[Test]
    public function isUsableWhereverTheCoreServiceIsExpected(): void
    {
        $this->initializeEnvironment('Production', false);
        $service = new LockableOpcodeCacheService();
        self::assertInstanceOf(OpcodeCacheService::class, $service);
        // Blocked full reset and allowed targeted invalidation both complete without side effects here.
        $service->clearAllActive();
        $service->clearAllActive(__FILE__);
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
