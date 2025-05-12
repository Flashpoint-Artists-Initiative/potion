<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ArtProjectStatusEnum;
use App\Filament\Admin\Resources\ArtProjectResource\Pages;
use App\Models\Event;
use App\Models\Grants\ArtProject;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ArtProjectResource extends Resource
{
    protected static ?string $model = ArtProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Event Specific';

    protected static ?string $navigationLabel = 'Art Grants';

    public static function shouldRegisterNavigation(): bool
    {
        return Event::where('id', Event::getCurrentEventId())->exists();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_status')
                    ->options(ArtProjectStatusEnum::toArray())
                    ->default(ArtProjectStatusEnum::PendingReview)
                    ->required(),
                Forms\Components\Fieldset::make('Basic Info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Project Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('artist_name')
                            ->label('Artist Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('short_description')
                            ->columnSpanFull()
                            ->helperText('Optional text to show in the list of projects. If left blank, the first 300 characters of the description will be used.'),
                    ]),
                Forms\Components\Fieldset::make('Funding')
                    ->schema([
                        Forms\Components\TextInput::make('min_funding')
                            ->label('Minimum Funding')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->lte('max_funding'),
                        Forms\Components\TextInput::make('max_funding')
                            ->label('Maximum Funding')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->gte('min_funding'),
                        Forms\Components\TextInput::make('budget_link')
                            ->maxLength(255),
                    ])
                    ->columns(3),
                SpatieMediaLibraryFileUpload::make('images')
                    ->hint('The first image will be what is shown in the list view.')
                    ->image()
                    ->imageEditor()
                    ->multiple()
                    ->reorderable()
                    ->panelLayout('grid')
                    ->appendFiles()
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $dollarsPerVote = Event::getCurrentEvent()->dollarsPerVote ?? 1.0;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('user.display_name')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->user_id ? UserResource::getUrl('view', ['record' => $record->user_id]) : null)
                    ->color('primary')
                    ->icon('heroicon-m-user')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('artist_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('min_funding')
                    ->numeric()
                    ->prefix('$')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('max_funding')
                    ->numeric()
                    ->prefix('$')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('totalVotes')
                    ->label('Votes')
                    ->numeric()
                    ->sortable(query: function (Builder $query, string $direction) {
                        // This query is necessary to sort based on a calculated column and not get duplicate models
                        // Copy below and to BulkAdjustArtProjects
                        return $query
                            ->select(['art_projects.*', DB::raw('sum(project_user_votes.votes) as totalVotes')])
                            ->leftJoin('project_user_votes', 'project_user_votes.art_project_id', '=', 'art_projects.id')
                            ->groupBy('art_projects.id')
                            ->orderBy('totalVotes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('totalFunding')
                    ->label('Funding')
                    ->numeric()
                    ->prefix('$')
                    ->sortable(query: function (Builder $query, string $direction) use ($dollarsPerVote) {
                        return $query
                            ->select(['art_projects.*', DB::raw('sum(project_user_votes.votes) as totalVotes')])
                            ->leftJoin('project_user_votes', 'project_user_votes.art_project_id', '=', 'art_projects.id')
                            ->groupBy('art_projects.id')
                            ->orderByRaw(sprintf('(COALESCE(totalVotes,0) * %f) + committee_funding %s', $dollarsPerVote, $direction));
                    })
                    ->toggleable(),
                // Tables\Columns\SelectColumn::make('project_status')
                //     ->label('Status')
                //     ->options(ArtProjectStatusEnum::class)
                //     ->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('project_status')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('budget')
                    ->url(fn ($record) => $record->budget_link, true)
                    ->icon('heroicon-m-link')
                    ->color('primary')
                    ->label(' Budget')
                    ->visible(fn ($record) => $record->budget_link),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtProjects::route('/'),
            'adjustments' => Pages\BulkAdjustArtProjects::route('/adjust'),
            'create' => Pages\CreateArtProject::route('/create'),
            'view' => Pages\ViewArtProject::route('/{record}'),
            'edit' => Pages\EditArtProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('event_id', Event::getCurrentEventId());
    }
}
