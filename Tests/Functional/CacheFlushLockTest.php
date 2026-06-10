<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Wazum\CacheFlushLock\Cache\LockableCacheManager;
use Wazum\CacheFlushLock\Service\LockableOpcodeCacheService;

final class CacheFlushLockTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['wazum/cache-flush-lock'];

    // The testing framework forces hash/imagesizes/pages/rootline to NullBackend,
    // which would make the pages-flush assertions pass vacuously — restore a real backend.
    // The __UNSET token removes the compression option (inherited from DefaultConfiguration)
    // which TransientMemoryBackend does not accept.
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => TransientMemoryBackend::class,
                        'options' => [
                            'compression' => '__UNSET',
                        ],
                    ],
                ],
            ],
        ],
    ];

    private bool $originalCli;
    private ApplicationContext $originalContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCli = Environment::isCli();
        $this->originalContext = Environment::getContext();
    }

    protected function tearDown(): void
    {
        $this->initializeEnvironment($this->originalContext, $this->originalCli);
        parent::tearDown();
    }

    #[Test]
    public function bootedSystemUsesLockedCacheManager(): void
    {
        self::assertInstanceOf(LockableCacheManager::class, $this->get(CacheManager::class));
    }

    #[Test]
    public function bootedSystemRegistersLockedOpcodeCacheServiceImplementation(): void
    {
        // On cold boots the early PackageManager cache write primes GeneralUtility's
        // final-class-name cache with the plain class before this extension's registration
        // runs, and the unguarded core factory then pins that instance into the container
        // for the request (CacheManager is immune — its factory has a boot.state guard).
        // From the first warm request on, the container provides the subclass — asserted
        // here via the XCLASS mapping, which getClassName() consults uncached.
        self::assertSame(
            LockableOpcodeCacheService::class,
            GeneralUtility::getClassName(OpcodeCacheService::class),
        );
    }

    #[Test]
    public function systemGroupFlushIsBlockedInProductionWebContext(): void
    {
        $cacheManager = $this->get(CacheManager::class);
        $assetsCache = $cacheManager->getCache('assets');
        $assetsCache->set('lockProbe', 'value');

        $this->initializeEnvironment(new ApplicationContext('Production'), false);
        $cacheManager->flushCachesInGroup('system');

        self::assertTrue($assetsCache->has('lockProbe'));
    }

    #[Test]
    public function pagesGroupFlushStillWorksInProductionWebContext(): void
    {
        $cacheManager = $this->get(CacheManager::class);
        $pagesCache = $cacheManager->getCache('pages');
        $pagesCache->set('lockProbe', 'value');

        $this->initializeEnvironment(new ApplicationContext('Production'), false);
        $cacheManager->flushCachesInGroup('pages');

        self::assertFalse($pagesCache->has('lockProbe'));
    }

    #[Test]
    public function systemGroupFlushWorksOnCommandLine(): void
    {
        $cacheManager = $this->get(CacheManager::class);
        $assetsCache = $cacheManager->getCache('assets');
        $assetsCache->set('lockProbe', 'value');

        $cacheManager->flushCachesInGroup('system');

        self::assertFalse($assetsCache->has('lockProbe'));
    }

    #[Test]
    public function flushAllKeepsSystemCachesInProductionWebContext(): void
    {
        $cacheManager = $this->get(CacheManager::class);
        $assetsCache = $cacheManager->getCache('assets');
        $assetsCache->set('lockProbe', 'value');
        $pagesCache = $cacheManager->getCache('pages');
        $pagesCache->set('lockProbe', 'value');

        $this->initializeEnvironment(new ApplicationContext('Production'), false);
        $cacheManager->flushCaches();

        self::assertTrue($assetsCache->has('lockProbe'));
        self::assertFalse($pagesCache->has('lockProbe'));
    }

    private function initializeEnvironment(ApplicationContext $context, bool $cli): void
    {
        Environment::initialize(
            $context,
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
