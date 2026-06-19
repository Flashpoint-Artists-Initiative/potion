<?php

declare(strict_types=1);

namespace Tests\Unit\Volunteering;

use App\Data\Volunteering\CalendarSelection;
use App\Models\Event;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use App\Services\ShiftSchedulingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShiftSchedulingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ShiftSchedulingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ShiftSchedulingService;
    }

    #[Test]
    public function calendar_selection_computes_snapped_start_and_length(): void
    {
        $team = $this->createTeam();
        $shiftType = ShiftType::factory()->for($team)->create();

        $selection = CalendarSelection::fromRaw([
            'startStr' => '2026-06-01 08:07:00',
            'endStr' => '2026-06-01 10:00:00',
            'resource' => ['id' => $shiftType->id],
        ], $team->event);

        $this->assertSame(0, $selection->startOffset);
        $this->assertSame($shiftType->id, $selection->shiftType?->id);
        $this->assertSame(2.0, $selection->lengthHours);
    }

    #[Test]
    public function calendar_selection_to_create_form_defaults(): void
    {
        $team = $this->createTeam();
        $shiftType = ShiftType::factory()->for($team)->create();

        $selection = CalendarSelection::fromRaw([
            'startStr' => '2026-06-01 10:00:00',
            'endStr' => '2026-06-01 12:00:00',
            'resource' => ['id' => $shiftType->id],
        ], $team->event);

        $this->assertSame([
            'start_offset' => 120,
            'length_in_hours' => 2.0,
            'multiplier' => '1',
            'shift_type_id' => $shiftType->id,
            'num_spots' => $shiftType->num_spots,
        ], $selection->toCreateFormDefaults());
    }

    #[Test]
    public function create_from_date_click_uses_shift_type_defaults(): void
    {
        $team = $this->createTeam();
        $shiftType = ShiftType::factory()->for($team)->create([
            'length' => 120,
            'num_spots' => 4,
        ]);

        $shift = $this->service->createFromDateClick($shiftType, '2026-06-01 09:00:00');

        $this->assertSame(60, $shift->start_offset);
        $this->assertSame(120, $shift->length);
        $this->assertSame(4, $shift->num_spots);
        $this->assertSame($shiftType->id, $shift->shift_type_id);
    }

    #[Test]
    public function create_from_form_data_persists_form_attributes(): void
    {
        $team = $this->createTeam();
        $shiftType = ShiftType::factory()->for($team)->create();

        $shift = $this->service->createFromFormData($team, [
            'shift_type_id' => $shiftType->id,
            'start_offset' => 180,
            'length_in_hours' => 3,
            'num_spots' => 2,
            'multiplier' => '1.5',
        ]);

        $this->assertSame(180, $shift->start_offset);
        $this->assertSame(180, $shift->length);
        $this->assertSame(2, $shift->num_spots);
        $this->assertSame('1.5', $shift->multiplier);
    }

    #[Test]
    public function move_by_minutes_updates_start_offset(): void
    {
        $shift = Shift::factory()->create(['start_offset' => 60]);

        $this->assertTrue($this->service->moveByMinutes($shift, 15));
        $shift->refresh();
        $this->assertSame(75, $shift->start_offset);
    }

    #[Test]
    public function move_by_zero_minutes_returns_false(): void
    {
        $shift = Shift::factory()->create(['start_offset' => 60]);

        $this->assertFalse($this->service->moveByMinutes($shift, 0));
    }

    #[Test]
    public function resize_by_minutes_updates_length(): void
    {
        $shift = Shift::factory()->create(['length' => 60]);

        $this->assertTrue($this->service->resizeByMinutes($shift, 30));
        $shift->refresh();
        $this->assertSame(90, $shift->length);
    }

    private function createTeam(): Team
    {
        $event = Event::factory()->create([
            'settings' => [
                'timezone' => 'America/New_York',
                'volunteering' => [
                    'base_date' => '2026-06-01 08:00:00',
                ],
            ],
        ]);

        return Team::factory()->for($event)->create();
    }
}
