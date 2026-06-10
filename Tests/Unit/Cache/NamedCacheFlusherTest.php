<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Wazum\CacheGuard\Cache\NamedCacheFlusher;

final class NamedCacheFlusherTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function flushesNamedCacheLeavingOthersIntact(): void
    {
        $cacheManager = new CacheManager();
        $target = $this->registerCacheWithEntry($cacheManager, 'fluid_template');
        $other = $this->registerCacheWithEntry($cacheManager, 'pages');

        $unknown = (new NamedCacheFlusher())->flush($cacheManager, ['fluid_template']);

        self::assertSame([], $unknown);
        self::assertFalse($target->has('entry'));
        self::assertTrue($other->has('entry'));
    }

    #[Test]
    public function flushesMultipleNamedCaches(): void
    {
        $cacheManager = new CacheManager();
        $first = $this->registerCacheWithEntry($cacheManager, 'fluid_template');
        $second = $this->registerCacheWithEntry($cacheManager, 'l10n');

        $unknown = (new NamedCacheFlusher())->flush($cacheManager, ['fluid_template', 'l10n']);

        self::assertSame([], $unknown);
        self::assertFalse($first->has('entry'));
        self::assertFalse($second->has('entry'));
    }

    #[Test]
    public function returnsUnknownIdentifiersAndFlushesNothing(): void
    {
        $cacheManager = new CacheManager();
        $known = $this->registerCacheWithEntry($cacheManager, 'fluid_template');

        $unknown = (new NamedCacheFlusher())->flush($cacheManager, ['fluid_template', 'does_not_exist']);

        self::assertSame(['does_not_exist'], $unknown);
        self::assertTrue($known->has('entry'));
    }

    #[Test]
    public function flushesNothingForEmptyInput(): void
    {
        $cacheManager = new CacheManager();
        $cache = $this->registerCacheWithEntry($cacheManager, 'fluid_template');

        $unknown = (new NamedCacheFlusher())->flush($cacheManager, []);

        self::assertSame([], $unknown);
        self::assertTrue($cache->has('entry'));
    }

    private function registerCacheWithEntry(CacheManager $cacheManager, string $identifier): VariableFrontend
    {
        $cache = new VariableFrontend($identifier, new TransientMemoryBackend([]));
        $cacheManager->registerCache($cache);
        $cache->set('entry', 'value');
        return $cache;
    }
}
