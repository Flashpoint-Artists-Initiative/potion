<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShiftResource\Pages;
use App\Filament\Admin\Resources\ShiftResource\RelationManagers;
use App\Filament\Admin\Resources\ShiftResource\ShiftForm;
use App\Filament\Admin\Resources\ShiftResource\ShiftFormContext;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\ShiftType;
use App\Models\Volunteering\Team;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $parentResource = ShiftTypeResource::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        /** @var ?Team $team */
        $team = $schema->getExtraAttributes()['team'] ?? null;
        /** @var ?ShiftType $shiftType */
        $shiftType = $schema->getExtraAttributes()['shiftType'] ?? null;

        $context = $team !== null
            ? ShiftFormContext::TeamCreate
            : ShiftFormContext::ShiftTypeCreate;

        return ShiftForm::configure($schema, $context, $team, $shiftType);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VolunteersRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
            'view' => Pages\ViewShift::route('/{record}'),
        ];
    }

    /**
     * @param  'view'|'edit'  $name
     */
    public static function getRecordUrl(string $name, Shift $record): string
    {
        return static::getUrl($name, [
            'record' => $record,
            'team' => $record->shiftType->team_id,
            'shift_type' => $record->shift_type_id,
        ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            TeamResource\Pages\ViewTeam::class,
            TeamResource\Pages\EditTeam::class,
            TeamResource\Pages\ShiftCalendar::class,
            TeamResource\Pages\ManageShiftTypes::class,
            TeamResource\Pages\ManageShifts::class,
        ]);
    }

    public static function getBreadcrumbRecordLabel(Shift $record): string
    {
        return $record->title . ' - ' . $record->start_carbon->format('D n/j, g:i A');
    }
}
