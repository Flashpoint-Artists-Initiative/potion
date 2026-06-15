<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWaiver extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected static ?string $navigationLabel = 'Waiver';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.admin.resources.event-resource.pages.view-waiver';

    public bool $hasWaiver = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->url(EditWaiver::getUrl(['record' => $this->record]))
                ->label(fn () => $this->hasWaiver ? 'Edit Waiver' : 'Create Waiver')
                ->hidden(function () {
                    /** @var Event $event */
                    $event = $this->record;

                    return $event->completedWaivers()->exists();
                }),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Waiver';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Event $model */
        $model = $this->getRecord();

        $this->hasWaiver = $model->waiver !== null;
    }
}
