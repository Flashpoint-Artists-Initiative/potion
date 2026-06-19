<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Volunteering\CalendarSelection;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Illuminate\Database\QueryException;

class ShiftSchedulingService
{
    public function createFromDateClick(ShiftType $shiftType, string $dateStr): Shift
    {
        $event = $shiftType->team->event;
        $startOffset = $event->roundedMinutesFromVolunteerBase($dateStr);

        return Shift::create([
            'start_offset' => $startOffset,
            'length' => $shiftType->length,
            'num_spots' => $shiftType->num_spots,
            'shift_type_id' => $shiftType->id,
            'multiplier' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFromSelection(Team $team, CalendarSelection $selection, array $attributes): Shift
    {
        $shiftTypeId = (int) ($attributes['shift_type_id'] ?? $selection->shiftType?->id);

        $shiftType = ShiftType::query()
            ->where('team_id', $team->id)
            ->findOrFail($shiftTypeId);

        return Shift::create([
            'start_offset' => (int) ($attributes['start_offset'] ?? $selection->startOffset),
            'length_in_hours' => $attributes['length_in_hours'] ?? $selection->lengthHours,
            'num_spots' => (int) ($attributes['num_spots'] ?? $shiftType->num_spots),
            'shift_type_id' => $shiftType->id,
            'multiplier' => $attributes['multiplier'] ?? '1',
        ]);
    }

    public function moveByMinutes(Shift $shift, int $deltaMinutes): bool
    {
        if ($deltaMinutes === 0) {
            return false;
        }

        $shift->start_offset += $deltaMinutes;

        try {
            $shift->save();
        } catch (QueryException) {
            return false;
        }

        return true;
    }

    public function resizeByMinutes(Shift $shift, int $deltaMinutes): bool
    {
        if ($deltaMinutes <= 0) {
            return false;
        }

        $shift->length += $deltaMinutes;

        try {
            $shift->save();
        } catch (QueryException) {
            return false;
        }

        return true;
    }
}
