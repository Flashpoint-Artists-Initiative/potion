<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Enums\PageContentEnum;
use App\Filament\Admin\Resources\EventResource;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditPageContent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected static ?string $navigationLabel = 'Page Content';

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    public function getBreadcrumb(): string
    {
        return 'Page Content';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('header')
                    ->label('')
                    ->content('Add content of various pages for this specific event.  The content will appear at the top of each page.'),
                Select::make('page')
                    ->options(PageContentEnum::class)
                    ->dehydrated(false)
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateHydrated(function (Select $component) {
                        // default() doesn't work. This sets the default value when the array is empty
                        $component->state(PageContentEnum::AppDashboard->value);
                    }),
                Grid::make(1)
                    ->schema($this->generatePageSchemas()),
            ]);
    }

    /**
     * @return array<mixed>
     */
    protected function generatePageSchemas(): array
    {
        $output = [];
        foreach (PageContentEnum::cases() as $page) {
            $relationship = Str::camel($page->value) . 'Content';
            $schema = [];

            $schema[] = Placeholder::make('title')
                ->label($page->getLabel());

            if ($page->hasTitle()) {
                $schema[] = Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->maxLength(255)
                    ->columnSpanFull();
            }

            $schema[] = Forms\Components\RichEditor::make('content')
                ->disableToolbarButtons(['attachFiles'])
                ->required()
                ->columnSpanFull();

            $output[] = Section::make($schema)
                ->visible(fn (Get $get) => $get('page') === $page->value)
                ->relationship($relationship)
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => array_merge($data, ['page' => $page->value]));
        }

        return $output;
    }
}
