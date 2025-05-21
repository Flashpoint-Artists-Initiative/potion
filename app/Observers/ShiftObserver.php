<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Volunteering\Shift;

class ShiftObserver
{
    public function creating(Shift $shift): void
    {
        $this->adjustLength($shift);
    }

    public function updating(Shift $shift): void
    {
        $this->adjustLength($shift);
    }

    public function updated(Shift $shift): void
    {
        if ($shift->notifyVolunteersOnChange) {
            // Notify volunteers of shift update
        }
    }

    public function deleting(Shift $shift): void
    {
        if ($shift->notifyVolunteersOnChange) {
            // Notify volunteers of shift deletion
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
}
