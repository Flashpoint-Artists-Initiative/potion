<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ArtProjectResource\Pages;

use App\Enums\ArtProjectStatusEnum;
use App\Enums\GrantFundingStatusEnum;
use App\Enums\LockdownEnum;
use App\Filament\Admin\Resources\ArtProjectResource;
use App\Models\Event;
use App\Models\Grants\ArtProject;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class BulkAdjustArtProjects extends ListRecords
{
    protected static string $resource = ArtProjectResource::class;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    protected static ?string $breadcrumb = 'Bulk Adjustments';

    public static function canAccess(array $parameters = []): bool
    {
        return Auth::user()?->can('artProjects.update') && ! LockdownEnum::Grants->isLocked();
    }

    public function table(Table $table): Table
    {
        $dollarsPerVote = Event::getCurrentEvent()->dollarsPerVote ?? 1.0;

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
                    ->sortable(query: function (EloquentBuilder $query, string $direction) {
                        // Copied from ArtProjectResource - Table - totalVotes - sortable
                        return $query
                            ->select(['art_projects.*', DB::raw('sum(project_user_votes.votes) as totalVotes')])
                            ->leftJoin('project_user_votes', 'project_user_votes.art_project_id', '=', 'art_projects.id')
                            ->groupBy('art_projects.id')
                            ->orderBy('totalVotes', $direction);
                    })
                    ->toggleable()
                    ->summarize(Summarizer::make()
                        ->using(fn (Builder $query) => $query->join('project_user_votes', 'art_project_id', '=', 'art_projects.id')->sum('votes'))
                    ),
                Columns\TextColumn::make('communityFunding')
                    ->label(new HtmlString('Community<br>Funding'))
                    ->numeric()
                    ->prefix('$')
                    ->sortable(query: function (EloquentBuilder $query, string $direction) {
                        return $query
                            ->select(['art_projects.*', DB::raw('sum(project_user_votes.votes) as totalVotes')])
                            ->leftJoin('project_user_votes', 'project_user_votes.art_project_id', '=', 'art_projects.id')
                            ->groupBy('art_projects.id')
                            ->orderByRaw(sprintf('COALESCE(totalVotes,0) %s', $direction));
                    })->toggleable()
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
                    ->summarize(Sum::make()->prefix('$'))
                    ->updateStateUsing(fn (ArtProject $record, $state) => $record->update(['committee_funding' => $state ?? 0])),
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
