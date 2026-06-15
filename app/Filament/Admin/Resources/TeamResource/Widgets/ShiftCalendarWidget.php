<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Widgets;

use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Guava Calendar v3 widget stub — full v1 implementation preserved in ShiftCalendarWidgetLegacy.
 *
 * TODO: Port ShiftCalendarWidgetLegacy to the guava/calendar v3 API.
 */
class ShiftCalendarWidget extends CalendarWidget
{
    public Team $record;

    protected CalendarViewType $calendarView = CalendarViewType::TimeGridWeek;

    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected bool $eventResizeEnabled = true;

    /**
     * @return array<int, mixed>|Collection<int, Shift>|Builder
     */
    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return Shift::query()
            ->whereHas('shiftType', fn (Builder $query) => $query->where('team_id', $this->record->id));
    }
}
