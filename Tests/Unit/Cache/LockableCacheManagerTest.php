<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheFlushLock\Cache\LockableCacheManager;
use Wazum\CacheFlushLock\Tests\Unit\Cache\Fixtures\FlushRecordingBackend;

final class LockableCacheManagerTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;
    protected bool $resetSingletonInstances = true;

    protected function tearDown(): void
    {
        FlushRecordingBackend::$flushedCacheIdentifiers = [];
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cache_flush_lock']);
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

    /** @param list<string> $groups */
    private function registerCacheWithEntry(LockableCacheManager $cacheManager, string $identifier, array $groups): VariableFrontend
    {
        $cache = new VariableFrontend($identifier, new TransientMemoryBackend());
        $cacheManager->registerCache($cache, $groups);
        $cache->set('entry', 'value');
        return $cache;
    }

    #[Test]
    public function doesNotFlushLockedGroupInLockedContext(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $systemCache = $this->registerCacheWithEntry($cacheManager, 'fluid_template', ['system']);

        $cacheManager->flushCachesInGroup('system');

        self::assertTrue($systemCache->has('entry'));
    }

    #[Test]
    public function flushesUnlockedGroupInLockedContext(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $pagesCache = $this->registerCacheWithEntry($cacheManager, 'pages', ['pages']);

        $cacheManager->flushCachesInGroup('pages');

        self::assertFalse($pagesCache->has('entry'));
    }

    #[Test]
    public function flushesLockedGroupOnCommandLine(): void
    {
        $this->initializeEnvironment('Production', true);
        $cacheManager = new LockableCacheManager();
        $systemCache = $this->registerCacheWithEntry($cacheManager, 'fluid_template', ['system']);

        $cacheManager->flushCachesInGroup('system');

        self::assertFalse($systemCache->has('entry'));
    }

    #[Test]
    public function flushesLockedGroupInDevelopmentContext(): void
    {
        $this->initializeEnvironment('Development', false);
        $cacheManager = new LockableCacheManager();
        $systemCache = $this->registerCacheWithEntry($cacheManager, 'fluid_template', ['system']);

        $cacheManager->flushCachesInGroup('system');

        self::assertFalse($systemCache->has('entry'));
    }

    #[Test]
    public function flushCachesSkipsLockedGroupCaches(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $systemCache = $this->registerCacheWithEntry($cacheManager, 'fluid_template', ['system']);
        $pagesCache = $this->registerCacheWithEntry($cacheManager, 'pages', ['pages']);

        $cacheManager->flushCaches();

        self::assertTrue($systemCache->has('entry'));
        self::assertFalse($pagesCache->has('entry'));
    }

    #[Test]
    public function flushCachesProtectsCacheBelongingToLockedAndUnlockedGroup(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $sharedCache = $this->registerCacheWithEntry($cacheManager, 'shared', ['system', 'all']);

        $cacheManager->flushCaches();

        self::assertTrue($sharedCache->has('entry'));
    }

    #[Test]
    public function flushCachesFlushesGrouplessCache(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $grouplessCache = $this->registerCacheWithEntry($cacheManager, 'groupless', []);

        $cacheManager->flushCaches();

        self::assertFalse($grouplessCache->has('entry'));
    }

    #[Test]
    public function flushCachesProtectsLazilyConfiguredLockedGroupCache(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $cacheManager->setCacheConfigurations([
            'lazySystem' => [
                'frontend' => VariableFrontend::class,
                'backend' => FlushRecordingBackend::class,
                'groups' => ['system'],
                'options' => [],
            ],
            'lazyPages' => [
                'frontend' => VariableFrontend::class,
                'backend' => FlushRecordingBackend::class,
                'groups' => ['pages'],
                'options' => [],
            ],
        ]);

        $cacheManager->flushCaches();

        self::assertContains('lazyPages', FlushRecordingBackend::$flushedCacheIdentifiers);
        self::assertNotContains('lazySystem', FlushRecordingBackend::$flushedCacheIdentifiers);
    }

    #[Test]
    public function doesNotFlushLockedGroupByTag(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $systemCache = new VariableFrontend('fluid_template', new TransientMemoryBackend());
        $cacheManager->registerCache($systemCache, ['system']);
        $systemCache->set('entry', 'value', ['someTag']);

        $cacheManager->flushCachesInGroupByTag('system', 'someTag');

        self::assertTrue($systemCache->has('entry'));
    }

    #[Test]
    public function doesNotFlushLockedGroupByTags(): void
    {
        $this->initializeEnvironment('Production', false);
        $cacheManager = new LockableCacheManager();
        $systemCache = new VariableFrontend('fluid_template', new TransientMemoryBackend());
        $cacheManager->registerCache($systemCache, ['system']);
        $systemCache->set('entry', 'value', ['someTag']);

        $cacheManager->flushCachesInGroupByTags('system', ['someTag']);

        self::assertTrue($systemCache->has('entry'));
    }
}
