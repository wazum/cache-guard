<?php

declare(strict_types=1);

namespace Wazum\CacheFlushLock\EventListener;

use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use Wazum\CacheFlushLock\Lock\FlushLock;

final readonly class RemoveLockedClearCacheActions
{
    public function __construct(
        private FlushLock $flushLock,
    ) {}

    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        if (!$this->flushLock->isAnyGroupLocked()) {
            return;
        }
        $event->setCacheActions(array_values(array_filter(
            $event->getCacheActions(),
            static fn(array $cacheAction): bool => $cacheAction['id'] !== 'all',
        )));
        $event->setCacheActionIdentifiers(array_values(array_diff($event->getCacheActionIdentifiers(), ['all'])));
    }
}
