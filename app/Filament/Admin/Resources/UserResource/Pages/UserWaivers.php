<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Ticketing\CompletedWaiver;
use App\Models\Ticketing\Order;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class UserWaivers extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'waivers';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return 'Waivers';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('waiver.event.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('waiver.title')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York')
                    ->sortable()
                    ->label('Created'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->modalHeading(fn (CompletedWaiver $record): string => "Delete Signed Waiver for {$record->waiver->title}"),
                // Tables\Actions\ViewAction::make()
                //     ->url(fn (Order $record): string => ViewOrder::getUrl(['record' => $record->id])),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
