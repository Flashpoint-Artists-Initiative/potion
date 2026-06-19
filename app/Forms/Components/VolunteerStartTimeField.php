<?php

declare(strict_types=1);

namespace App\Forms\Components;

use App\Models\Event;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;

/**
 * A datetime picker that reads and writes volunteer start offsets (minutes from base date).
 *
 * Filament's DateTimeStateCast treats integer state as Unix timestamps, so it is excluded.
 * Offsets are converted to picker display strings on hydrate and back to offsets on dehydrate.
 */
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
            ->afterStateHydrated(function (VolunteerStartTimeField $component): void {
                $state = $component->getRawState();

                if (! is_int($state) && ! (is_string($state) && is_numeric($state))) {
                    return;
                }

                $component->state($component->getEvent()->formatOffsetForFilamentPicker((int) $state));
            })
            ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state)
                ? $this->getEvent()->offsetFromFilamentPickerState($state)
                : null);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return array_values(array_filter(
            parent::getDefaultStateCasts(),
            fn (mixed $cast): bool => ! $cast instanceof DateTimeStateCast,
        ));
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
