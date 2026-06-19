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
        if ($context === Context::EventClick && ! $this->calendarEventIsInteractive($data)) {
            return collect();
        }

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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function calendarEventIsInteractive(array $data): bool
    {
        if (data_get($data, 'event.extendedProps.clickable') === false) {
            return false;
        }

        return filled(data_get($data, 'event.extendedProps.model'))
            && filled(data_get($data, 'event.extendedProps.key'));
    }
}
