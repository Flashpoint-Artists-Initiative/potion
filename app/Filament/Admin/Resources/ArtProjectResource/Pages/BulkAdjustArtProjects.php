<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArtProjectResource\Pages;

use App\Enums\ArtProjectStatusEnum;
use App\Enums\GrantFundingStatusEnum;
use App\Filament\Admin\Resources\ArtProjectResource;
use App\Models\Event;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\HtmlString;

class BulkAdjustArtProjects extends ListRecords
{
    protected static string $resource = ArtProjectResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    protected static ?string $breadcrumb = 'Bulk Adjustments';

    public function table(Table $table): Table
    {
        $dollarsPerVote = Event::getCurrentEvent()->dollarsPerVote ?? 1;

        return $table
            ->columns([
                Columns\TextColumn::make('fundingStatus')
                    ->formatStateUsing(fn (GrantFundingStatusEnum $state) => match ($state) {
                        GrantFundingStatusEnum::Unfunded => 'Unfunded',
                        GrantFundingStatusEnum::MinReached => 'Min Reached',
                        GrantFundingStatusEnum::MaxReached => 'Max Reached',
                    })
                    ->label('Funded')
                    ->grow(false)
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->name),
                Columns\TextColumn::make('artist_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('min_funding')
                    ->label(new HtmlString('<span class="text-sm">Min<br>Requested</span>'))
                    ->numeric()
                    ->prefix('$')
                    ->sortable()
                    ->toggleable()
                    ->summarize(Sum::make()->prefix('$')),
                Columns\TextColumn::make('max_funding')
                    ->label(new HtmlString('<span class="text-sm">Max<br>Requested</span>'))
                    ->numeric()
                    ->prefix('$')
                    ->sortable()
                    ->toggleable()
                    ->summarize(Sum::make()->prefix('$')),
                Columns\TextColumn::make('totalVotes')
                    ->label('Votes')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->summarize(Summarizer::make()
                        ->using(fn (Builder $query) => $query->join('project_user_votes', 'art_project_id', '=', 'art_projects.id')->sum('votes'))
                    ),
                Columns\TextColumn::make('totalFunding')
                    ->label(new HtmlString('Community<br>Funding'))
                    ->numeric()
                    ->prefix('$')
                    ->sortable()
                    ->toggleable()
                    ->summarize(Summarizer::make()
                        ->prefix('$')
                        ->using(fn (Builder $query) => $query
                            ->join('project_user_votes', 'art_project_id', '=', 'art_projects.id')->sum('votes')
                             * $dollarsPerVote)),
                Columns\TextInputColumn::make('committee_funding')
                    ->label(new HtmlString('Committee<br>Funding'))
                    ->type('number')
                    ->sortable()
                    ->toggleable()
                    ->summarize(Sum::make()->prefix('$')),
                Columns\SelectColumn::make('project_status')
                    ->label('Status')
                    ->options(ArtProjectStatusEnum::class)
                    ->selectablePlaceholder(false),
                Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('budget')
                    ->url(fn ($record) => $record->budget_link, true)
                    ->icon('heroicon-m-link')
                    ->color('primary')
                    ->label(' Budget'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
