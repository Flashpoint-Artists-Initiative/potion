<?php

declare(strict_types=1);

namespace App\Data\Volunteering;

use App\Models\Event;
use App\Models\Volunteering\ShiftType;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Parsed date/time range from a Guava Calendar interaction (drag-select or date click).
 *
 * Converts raw JS calendar context into event-timezone instants, volunteer start
 * offsets, and default form state for shift creation modals.
 */
readonly class CalendarSelection
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public int $startOffset,
        public float $lengthHours,
        public ?ShiftType $shiftType,
    ) {}

    /**
     * @param  array<string, mixed>|null  $rawContextData  Full Guava context payload from getRawCalendarContextData().
     */
    public static function fromWidgetContext(?array $rawContextData, Event $event, int $slotMinutes = Event::VOLUNTEER_SLOT_MINUTES): self
    {
        if ($rawContextData === null) {
            throw new InvalidArgumentException('Missing calendar context data');
        }

        $data = data_get($rawContextData, 'data');

        if (! is_array($data)) {
            throw new InvalidArgumentException('Calendar context data is missing the inner data payload');
        }

        return self::fromRaw($data, $event, $slotMinutes);
    }

    /**
     * @param  array<string, mixed>  $data  Raw Guava calendar context data (the inner `data` payload).
     */
    public static function fromRaw(array $data, Event $event, int $slotMinutes = Event::VOLUNTEER_SLOT_MINUTES): self
    {
        $startStr = self::requireString($data, 'startStr', 'start');
        $endStr = self::requireString($data, 'endStr', 'end');

        $start = Carbon::parse($startStr, $event->timezone);
        $end = Carbon::parse($endStr, $event->timezone)->roundMinutes($slotMinutes);
        $startOffset = $event->roundedMinutesFromVolunteerBase($start, $slotMinutes);
        $snappedStart = $event->volunteerDateTimeFromOffset($startOffset);

        $shiftType = null;
        $resourceId = data_get($data, 'resource.id');

        if ($resourceId !== null) {
            $found = ShiftType::query()->find($resourceId);
            $shiftType = $found instanceof ShiftType ? $found : null;
        }

        return new self(
            start: $start,
            end: $end,
            startOffset: $startOffset,
            lengthHours: max(0.25, $snappedStart->diffInMinutes($end) / 60),
            shiftType: $shiftType,
        );
    }

    /**
     * Default form state for drag-select shift creation.
     *
     * @return array<string, mixed>
     */
    public function toCreateFormDefaults(): array
    {
        return [
            'start_offset' => $this->startOffset,
            'length_in_hours' => $this->lengthHours,
            'multiplier' => '1',
            ...($this->shiftType !== null ? [
                'shift_type_id' => $this->shiftType->id,
                'num_spots' => $this->shiftType->num_spots,
            ] : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function requireString(array $data, string $primaryKey, string $fallbackKey): string
    {
        $value = $data[$primaryKey] ?? $data[$fallbackKey] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Calendar selection missing required date key: {$primaryKey}");
        }

        return $value;
    }
}
