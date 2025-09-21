<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Models\Event;
use App\Models\Ticketing\GateScan;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class History extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.gate.pages.history';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(GateScan::query()->where('event_id', Event::getCurrentEventId()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('D, n/j g:i A T', 'America/New_York')
                    ->sortable()
                    ->label('Scanned'),
                TextColumn::make('user.legal_name')
                    ->searchable()
                    ->label('Name')
                    ->url(fn (GateScan $record): string => Checkin::getUrl([
                        'userId' => $record->user_id,
                        'eventId' => $record->event_id,
                    ]))
                    ->color('primary'),
                TextColumn::make('wristband_number')
                    ->searchable()
                    ->label('Wristband Number'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
