<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Event;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventVolunteerOffsetTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function rounded_minutes_snaps_to_nearest_slot(): void
    {
        $event = $this->createEventWithTimezone('America/New_York');

        $base = $event->volunteerBaseDate;
        $sevenMinutesAfter = $base->copy()->addMinutes(7)->format('Y-m-d H:i:s');

        $this->assertSame(0, $event->roundedMinutesFromVolunteerBase($sevenMinutesAfter));
        $this->assertSame(15, $event->roundedMinutesFromVolunteerBase($base->copy()->addMinutes(8)->format('Y-m-d H:i:s')));
        $this->assertSame(15, $event->roundedMinutesFromVolunteerBase($base->copy()->addMinutes(14)->format('Y-m-d H:i:s')));
    }

    #[Test]
    public function minutes_from_volunteer_base_does_not_snap(): void
    {
        $event = $this->createEventWithTimezone('America/New_York');

        $base = $event->volunteerBaseDate;
        $sevenMinutesAfter = $base->copy()->addMinutes(7)->format('Y-m-d H:i:s');

        $this->assertSame(7, $event->minutesFromVolunteerBase($sevenMinutesAfter));
    }

    #[Test]
    public function offset_round_trip_in_different_timezones(): void
    {
        foreach (['America/New_York', 'America/Los_Angeles'] as $timezone) {
            $event = $this->createEventWithTimezone($timezone);
            $offset = 120;

            $datetime = $event->volunteerDateTimeFromOffset($offset);
            $this->assertSame($offset, $event->minutesFromVolunteerBase($datetime));
        }
    }

    #[Test]
    public function format_datetime_for_filament_picker_uses_utc_label(): void
    {
        $event = $this->createEventWithTimezone('America/New_York');
        $datetime = $event->volunteerDateTimeFromOffset(60);

        $formatted = $event->formatDateTimeForFilamentPicker($datetime);

        $this->assertStringContainsString('UTC', $formatted);
    }

    private function createEventWithTimezone(string $timezone): Event
    {
        return Event::factory()->create([
            'settings' => [
                'timezone' => $timezone,
                'volunteering' => [
                    'base_date' => '2026-06-01 08:00:00',
                ],
            ],
        ]);
    }
}
