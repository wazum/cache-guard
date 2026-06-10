<?php

declare(strict_types=1);

namespace Wazum\CacheGuard\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
            InputOption::VALUE_OPTIONAL,
            'Comma-separated cache identifiers to flush, e.g. fluid_template,l10n. Pass the option without a value to choose interactively.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->hasParameterOption('--cache')) {
            return parent::execute($input, $output);
        }

        $io = new SymfonyStyle($input, $output);
        $identifiers = GeneralUtility::trimExplode(',', (string) ($input->getOption('cache') ?? ''), true);

        if (in_array('di', $identifiers, true)) {
            $io->error('The dependency injection cache cannot be flushed via --cache. Use "cache:flush --group di".');
            return self::FAILURE;
        }
        if ($identifiers === [] && !$input->isInteractive()) {
            $io->error('No cache identifiers given.');
            return self::FAILURE;
        }

        $container = $this->bootService->getContainer(true);
        $this->bootService->loadExtLocalconfDatabaseAndExtTables(false, true);
        $cacheManager = $container->get(CacheManager::class);

        $flusher = new NamedCacheFlusher();
        $unknownIdentifiers = $flusher->flush($cacheManager, $identifiers);
        if ($identifiers !== [] && $unknownIdentifiers === []) {
            $io->success(sprintf('Flushed cache(s): %s', implode(', ', $identifiers)));
            return self::SUCCESS;
        }

        if (!$input->isInteractive()) {
            $io->error(sprintf(
                'Unknown cache identifier(s): %s. Valid identifiers: %s',
                implode(', ', $unknownIdentifiers),
                implode(', ', $this->validCacheIdentifiers()),
            ));
            return self::FAILURE;
        }

        if ($unknownIdentifiers !== []) {
            $io->warning(sprintf('Unknown cache identifier(s): %s', implode(', ', $unknownIdentifiers)));
        }
        $selectedIdentifiers = $this->askForCacheIdentifiers($io);
        $flusher->flush($cacheManager, $selectedIdentifiers);
        $io->success(sprintf('Flushed cache(s): %s', implode(', ', $selectedIdentifiers)));
        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function askForCacheIdentifiers(SymfonyStyle $io): array
    {
        $question = new ChoiceQuestion('Select caches to flush', $this->validCacheIdentifiers());
        $question->setMultiselect(true);

        /** @var list<string> $selected */
        $selected = $io->askQuestion($question);

        return $selected;
    }

    /**
     * @return list<string>
     */
    private function validCacheIdentifiers(): array
    {
        $identifiers = array_map(
            'strval',
            array_keys($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? []),
        );
        $identifiers = array_values(array_filter(
            $identifiers,
            static fn(string $identifier): bool => $identifier !== 'di',
        ));
        sort($identifiers);

        return $identifiers;
    }
}
