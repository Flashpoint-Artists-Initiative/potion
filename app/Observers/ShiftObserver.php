<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Notifications\ShiftDeletedNotification;
use App\Notifications\ShiftUpdatedNotification;

class ShiftObserver
{
    public function creating(Shift $shift): void
    {
        $this->adjustLength($shift);
    }

    public function updating(Shift $shift): void
    {
        $this->adjustLength($shift);

        if ($shift->notifyVolunteersOnChange) {
            // Notify volunteers of shift update
            $changes = $this->collectChanges($shift);

            if (! empty($changes)) {
                foreach ($shift->volunteers as $volunteer) {
                    $volunteer->notify(new ShiftUpdatedNotification($shift, $changes, $shift->changeReason));
                }
            }
        }
    }

    public function deleting(Shift $shift): void
    {
        if ($shift->notifyVolunteersOnChange) {
            // Notify volunteers of shift deletion
            foreach ($shift->volunteers as $volunteer) {
                $volunteer->notify(new ShiftDeletedNotification($shift));
            }
        }
    }

    /**
     * Set length via length_in_hours or the shiftType default length
     */
    protected function adjustLength(Shift $shift): void
    {
        $default = $shift->shiftType->length;

        if ($shift->length === null) {
            if ($shift->length_in_hours == null) {
                $shift->length = $default;

                return;
            }

            $shift->length = (int) ($shift->length_in_hours * 60);
        }
    }

    /**
     * Compile the changes made to the shift, for the notification
     *
     * @return array<string, array<string, scalar>>
     */
    protected function collectChanges(Shift $shift): array
    {
        // The changes we care about
        $attributes = ['start_offset', 'length', 'multiplier', 'shift_type_id'];
        $changes = $shift->getDirty();
        $changes = array_intersect_key($changes, array_flip($attributes));

        $output = [];
        foreach ($changes as $key => $value) {
            $output[$key] = [
                'label' => ucfirst($key),
                'old' => $shift->getOriginal($key),
                'new' => $value,
            ];
        }

        return $this->formatChanges($shift, $output);
    }

    /**
     * Format the changes for the notification
     *
     * @param  array<string, array<string, string|int>>  $changes
     * @return array<string, array<string, scalar>>
     */
    protected function formatChanges(Shift $shift, array $changes): array
    {
        if (array_key_exists('start_offset', $changes)) {
            $changes['start_offset']['label'] = 'Start Time';
            $changes['start_offset']['old'] = $this->formatOffset($shift, (int) $changes['start_offset']['old']);
            $changes['start_offset']['new'] = $this->formatOffset($shift, (int) $changes['start_offset']['new']);
        }

        if (array_key_exists('shift_type_id', $changes)) {
            $changes['shift_type_id']['label'] = 'Shift Type';
            $changes['shift_type_id']['old'] = ShiftType::where('id', $changes['shift_type_id']['old'])->firstOrFail()->title;
            $changes['shift_type_id']['new'] = ShiftType::where('id', $changes['shift_type_id']['new'])->firstOrFail()->title;
        }

        if (array_key_exists('length', $changes)) {
            $changes['length']['old'] = intval($changes['length']['old']) / 60 . ' hours';
            $changes['length']['new'] = intval($changes['length']['new']) / 60 . ' hours';
        }

        if (array_key_exists('multiplier', $changes)) {
            $changes['multiplier']['label'] = 'Shift Value';
            $changes['multiplier']['old'] .= 'x';
            $changes['multiplier']['new'] .= 'x';
        }

        return $changes;
    }

    protected function formatOffset(Shift $shift, int $offset): string
    {
        $start = $shift->team->event->volunteerBaseDate->copy();
        $start->addMinutes($offset);

        return $start->format('D F jS, Y g:i A T'); // Already in the correct timezone from the volunteerBaseDate mutator
    }
}
