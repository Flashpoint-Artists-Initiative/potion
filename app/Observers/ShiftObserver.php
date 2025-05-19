<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Volunteering\Shift;

class ShiftObserver
{
    public function creating(Shift $shift): void
    {
        // $this->adjustNumSpots($shift);
        // $this->adjustLength($shift);
    }

    public function updating(Shift $shift): void
    {
        // $this->adjustNumSpots($shift);
        // $this->adjustLength($shift);
    }
    
    /**
     * Don't set num_spots if it is the same as the shiftType value
     */
    protected function adjustNumSpots(Shift $shift): void
    {
        $default = $shift->shiftType->num_spots;

        if ($shift->num_spots == $default) {
            $shift->num_spots = null;
        }
    }

    /**
     * Don't set length if it is the same as the shiftType value
     */
    protected function adjustLength(Shift $shift): void
    {
        $default = $shift->shiftType->length;

        if ($shift->length == $default) {
            $shift->length = null;
        }
    }
}
