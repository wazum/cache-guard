<?php

declare(strict_types=1);

namespace Wazum\CacheGuard;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use Wazum\CacheGuard\Command\CacheFlushCommand;

final class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'wazum/cache-guard';
    }

    public function getFactories(): array
    {
        return [
            CacheFlushCommand::class => self::getCacheFlushCommand(...),
        ];
    }

    public function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            CommandRegistry::class => self::configureCommands(...),
        ]);
    }

    public static function getCacheFlushCommand(ContainerInterface $container): CacheFlushCommand
    {
        return new CacheFlushCommand(
            $container->get(BootService::class),
            $container->get('cache.di'),
        );
    }

    public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry
    {
        $commandRegistry->addLazyCommand(
            'cache:flush',
            CacheFlushCommand::class,
            'Flush TYPO3 caches: all, a group (--group), or named caches (--cache).',
        );
        return $commandRegistry;
    }
}
