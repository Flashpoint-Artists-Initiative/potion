<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\Event;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditWaiver extends EditRecord
{
    protected static string $resource = EventResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var Event $event */
        $event = $parameters['record'];

        return parent::canAccess($parameters) && $event->waiver && ! $event->completedWaivers()->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make('delete')
                // ->label('Delete Waiver')
                ->hidden(function () {
                    /** @var Event $event */
                    $event = $this->record;

                    return ! $event->waiver || $event->completedWaivers()->count() > 0;
                })
                ->action(function (Action $action) {
                    /** @var Event $event */
                    $event = $this->record;
                    if ($event->waiver?->delete()) {
                        $action->success();
                    } else {
                        $action->failure();
                    }

                })
                ->modalHeading('Delete Waiver')
                // ->successNotificationTitle('Deleted')
                // ->color('danger')
                // ->requiresConfirmation()
                ->successRedirectUrl($this->getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\RichEditor::make('content')
                        ->required()
                        ->columnSpanFull(),
                ])
                    ->relationship('waiver'),
            ]);
    }
}
