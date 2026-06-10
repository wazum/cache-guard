<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\EventListener;

use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use Wazum\CacheFlushLock\Lock\FlushLock;

#[AsEventListener('cache-flush-lock/remove-locked-clear-cache-actions')]
final readonly class RemoveLockedClearCacheActions
{
    private const INFORMATION_ACTION_ID = 'cacheFlushLockInformation';
    private const LANGUAGE_FILE = 'LLL:EXT:cache_flush_lock/Resources/Private/Language/locallang.xlf';

    public function __construct(
        private FlushLock $flushLock,
    ) {}

    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        if (!$this->flushLock->isAnyGroupLocked()) {
            return;
        }

        $remainingActions = array_values(array_filter(
            $event->getCacheActions(),
            static fn(array $cacheAction): bool => $cacheAction['id'] !== 'all',
        ));
        $remainingActions[] = [
            'id' => self::INFORMATION_ACTION_ID,
            'iconIdentifier' => 'actions-info-circle',
            'title' => self::LANGUAGE_FILE . ':toolbar.systemCachesLocked.title',
            'description' => self::LANGUAGE_FILE . ':toolbar.systemCachesLocked.description',
            'severity' => 'info',
        ];

        // The information row deliberately carries no endpoint so the dropdown JavaScript
        // ignores clicks on it; core's CacheAction shape requires one, hence the ignore.
        // @phpstan-ignore argument.type
        $event->setCacheActions($remainingActions);
        $event->setCacheActionIdentifiers(array_values(array_diff($event->getCacheActionIdentifiers(), ['all'])));
    }
}
