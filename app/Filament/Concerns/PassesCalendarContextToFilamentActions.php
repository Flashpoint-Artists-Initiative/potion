<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Guava\Calendar\Enums\Context;
use Illuminate\Support\Collection;

/**
 * Guava Calendar passes interaction context as raw JS payloads, but Filament v5
 * actions do not automatically receive those arguments. This trait re-invokes
 * cached context menu actions with the raw calendar data as action arguments.
 */
trait PassesCalendarContextToFilamentActions
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function getContextMenuActionsUsing(Context $context, array $data = []): Collection
    {
        $this->setRawCalendarContextData($context, $data);

        $actions = match ($context) {
            Context::EventClick => $this->getCachedEventClickContextMenuActions(),
            Context::DateClick => $this->getCachedDateClickContextMenuActions(),
            Context::DateSelect => $this->getCachedDateSelectContextMenuActions(),
            Context::NoEventsClick => $this->getCachedNoEventsClickContextMenuActions(),
            default => [],
        };

        return collect($actions)
            ->filter(fn (Action $action) => $action->isVisible())
            ->map(function (Action $action): string {
                $raw = $this->getRawCalendarContextData();
                /** @var array<string, mixed> $contextArguments */
                $contextArguments = is_array($raw) ? $raw : [];

                return ($action)($contextArguments)->toHtml();
            });
    }
}
