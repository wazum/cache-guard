<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Command\CacheFlushCommand as CoreCacheFlushCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Wazum\CacheGuard\Cache\NamedCacheFlusher;

final class CacheFlushCommand extends CoreCacheFlushCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this->addOption(
            'cache',
            null,
            InputOption::VALUE_REQUIRED,
            'Comma-separated cache identifiers to flush, e.g. fluid_template,l10n',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheOption = $input->getOption('cache');
        if ($cacheOption === null) {
            return parent::execute($input, $output);
        }

        $io = new SymfonyStyle($input, $output);
        $identifiers = GeneralUtility::trimExplode(',', (string) $cacheOption, true);
        if ($identifiers === []) {
            $io->error('No cache identifiers given.');
            return self::FAILURE;
        }
        if (in_array('di', $identifiers, true)) {
            $io->error('The dependency injection cache cannot be flushed via --cache. Use "cache:flush --group di".');
            return self::FAILURE;
        }

        $container = $this->bootService->getContainer(true);
        $this->bootService->loadExtLocalconfDatabaseAndExtTables(false, true);
        $cacheManager = $container->get(CacheManager::class);

        $unknownIdentifiers = (new NamedCacheFlusher())->flush($cacheManager, $identifiers);
        if ($unknownIdentifiers !== []) {
            $validIdentifiers = array_keys($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? []);
            sort($validIdentifiers);
            $io->error(sprintf(
                'Unknown cache identifier(s): %s. Valid identifiers: %s',
                implode(', ', $unknownIdentifiers),
                implode(', ', $validIdentifiers),
            ));
            return self::FAILURE;
        }

        $io->success(sprintf('Flushed cache(s): %s', implode(', ', $identifiers)));
        return self::SUCCESS;
    }
}
