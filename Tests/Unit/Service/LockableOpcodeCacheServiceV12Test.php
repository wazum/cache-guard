<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheGuard\Service\LockableOpcodeCacheServiceV12;

final class LockableOpcodeCacheServiceV12Test extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    #[Test]
    public function isUsableWhereverTheCoreServiceIsExpected(): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 13) {
            self::markTestSkipped('On TYPO3 v13+ OpcodeCacheService is readonly; the V12 subclass only loads on v12.');
        }

        $this->initializeEnvironment('Production', false);
        $service = new LockableOpcodeCacheServiceV12();
        self::assertInstanceOf(OpcodeCacheService::class, $service);
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
