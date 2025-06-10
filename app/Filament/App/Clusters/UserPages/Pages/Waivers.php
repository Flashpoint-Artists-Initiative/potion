<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Filament\App\Clusters\UserPages;
use App\Models\Ticketing\CompletedWaiver;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Waivers extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.app.clusters.user-pages.pages.waivers';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = UserPages::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(CompletedWaiver::where('user_id', Auth::id()))
            ->columns([
                TextColumn::make('waiver.event.name')
                    ->label('Event'),
                TextColumn::make('waiver.title')
                    ->label('Waiver')
                    ->limit(50),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalContent(fn (CompletedWaiver $record): View => view('filament.app.clusters.user-pages.pages.view-waiver', [
                        'waiver' => $record->waiver,
                        'completedWaiver' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalHeading(fn (CompletedWaiver $record): string => "{$record->waiver->event->name}: {$record->waiver->title}"),
            ])
            ->emptyStateHeading("You haven't signed any waivers.")
            ->paginated(false);
    }
}
