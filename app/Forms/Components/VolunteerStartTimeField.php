<?php

declare(strict_types=1);

namespace App\Forms\Components;

use App\Models\Event;
use Closure;
use Filament\Forms\Components\DateTimePicker;

class VolunteerStartTimeField extends DateTimePicker
{
    protected Event|Closure|null $event = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Start Time')
            ->required()
            ->seconds(false)
            ->step(Event::VOLUNTEER_SLOT_MINUTES * 60)
            ->format('Y-m-d H:i:s T')
            ->timezone(fn (): string => $this->getEvent()->timezone)
            ->formatStateUsing(function (mixed $state): ?string {
                if ($state === null || $state === '') {
                    return null;
                }

                if (is_string($state) && ! is_numeric($state)) {
                    return $state;
                }

                return $this->getEvent()->formatDateTimeForFilamentPicker(
                    $this->getEvent()->volunteerDateTimeFromOffset((int) $state)
                );
            })
            ->dehydrateStateUsing(function (mixed $state): ?int {
                if ($state === null || $state === '') {
                    return null;
                }

                if (is_int($state) || (is_string($state) && is_numeric($state))) {
                    return (int) $state;
                }

                return $this->getEvent()->roundedMinutesFromVolunteerBase((string) $state);
            });
    }

    public function event(Event|Closure $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getEvent(): Event
    {
        return $this->evaluate($this->event);
    }

    public static function make(?string $name = null): static
    {
        /** @var static $field */
        $field = parent::make($name ?? 'start_offset');

        return $field;
    }
}
