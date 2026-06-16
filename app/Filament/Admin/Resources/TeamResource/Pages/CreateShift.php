<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use App\Filament\Admin\Resources\TeamResource;
use App\Models\Volunteering\Shift;
use App\Models\Volunteering\Team;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateShift extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    public Team $ownerRecord;

    public function mount(): void
    {
        $record = request()->route('record');

        if (! is_int($record) && ! is_string($record)) {
            abort(404);
        }

        $ownerRecord = TeamResource::resolveRecordRouteBinding($record);

        if (! $ownerRecord instanceof Team) {
            abort(404);
        }

        $this->ownerRecord = $ownerRecord;

        abort_unless(TeamResource::canEdit($this->ownerRecord), 403);

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    public function mountParentRecord(): void
    {
        // Team-level create does not use nested parent association.
    }

    protected function authorizeAccess(): void
    {
        abort_unless(ShiftResource::canCreate(), 403);
    }

    public function form(Schema $schema): Schema
    {
        return ShiftResource::form($schema->extraAttributes([
            'team' => $this->ownerRecord,
        ]));
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->url(TeamResource::getUrl('shifts', ['record' => $this->ownerRecord]))
            ->color('gray');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new Shift($data);
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        /** @var Shift $record */
        $record = $this->getRecord();

        return ShiftResource::getRecordUrl('view', $record);
    }
}
