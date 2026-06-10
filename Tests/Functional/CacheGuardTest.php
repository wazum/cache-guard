<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Wazum\CacheGuard\Cache\LockableCacheManager;
use Wazum\CacheGuard\Command\CacheFlushCommand;
use Wazum\CacheGuard\Service\LockableOpcodeCacheService;
use Wazum\CacheGuard\ServiceProvider;

final class CacheGuardTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['wazum/cache-guard'];

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

    #[Test]
    public function flushAllToolbarActionIsRemovedInLockedContext(): void
    {
        $this->initializeEnvironment(new ApplicationContext('Production'), false);
        $event = new ModifyClearCacheActionsEvent(
            [['id' => 'pages'], ['id' => 'all']],
            ['pages', 'all'],
        );

        $this->get(EventDispatcherInterface::class)->dispatch($event);

        $actionIds = array_column($event->getCacheActions(), 'id');
        self::assertNotContains('all', $actionIds);
        self::assertContains('cacheGuardInformation', $actionIds);
    }

    #[Test]
    public function cacheFlushCommandIsOverriddenAndExposesCacheOption(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');

        self::assertInstanceOf(CacheFlushCommand::class, $command);
        self::assertTrue($command->getDefinition()->hasOption('cache'));
    }

    #[Test]
    public function serviceProviderParticipatesInTheFailsafeRegistry(): void
    {
        // The real CLI boots failsafe; only partOfMinimalUsableSystem packages are kept.
        // This proves our override reaches the failsafe cache:flush the binary actually runs.
        $registry = new ServiceProviderRegistry($this->get(PackageManager::class), true);
        $providerClasses = [];
        foreach ($registry as $provider) {
            $providerClasses[] = $provider::class;
        }

        self::assertContains(ServiceProvider::class, $providerClasses);
    }

    #[Test]
    public function cacheOptionRejectsDependencyInjectionCache(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--cache' => 'di']);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('--group di', $tester->getDisplay());
    }

    #[Test]
    public function cacheOptionFailsForEmptyValue(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--cache' => ''], ['interactive' => false]);

        self::assertSame(1, $exitCode);
    }

    #[Test]
    public function cacheOptionFailsForUnknownIdentifier(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--cache' => 'does_not_exist'], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('does_not_exist', $tester->getDisplay());
    }

    #[Test]
    public function interactiveCacheOptionOpensPickerForBareOption(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');
        $tester = new CommandTester($command);
        $tester->setInputs(['fluid_template']);

        $exitCode = $tester->execute(['--cache' => null], ['interactive' => true]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('fluid_template', $tester->getDisplay());
    }

    #[Test]
    public function interactiveCacheOptionFallsBackToPickerForUnknownValue(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');
        $tester = new CommandTester($command);
        $tester->setInputs(['fluid_template']);

        $exitCode = $tester->execute(['--cache' => 'does_not_exist'], ['interactive' => true]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('does_not_exist', $tester->getDisplay());
        self::assertStringContainsString('fluid_template', $tester->getDisplay());
    }

    #[Test]
    public function bareCacheOptionWithoutInteractionFails(): void
    {
        $command = $this->get(CommandRegistry::class)->getCommandByIdentifier('cache:flush');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--cache' => null], ['interactive' => false]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('No cache identifiers given', $tester->getDisplay());
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
