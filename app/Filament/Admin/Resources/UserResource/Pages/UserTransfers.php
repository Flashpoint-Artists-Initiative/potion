<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\TicketTransferResource;
use App\Filament\Admin\Resources\UserResource;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Ticketing\TicketTransfer;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class UserTransfers extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'ticketTransfers';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $title = 'Ticket Transfers';

    public static function getNavigationLabel(): string
    {
        return 'Ticket Transfers';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(function (Builder $query) {
                /** @var User $record */
                $record = $this->record;

                return $query
                    ->orWhere('ticket_transfers.recipient_user_id', $record->id)
                    ->orWhere('ticket_transfers.recipient_email', $record->email);
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('user')
                    ->label('Sender'),
                Tables\Columns\TextColumn::make('recipient_email')
                    ->searchable(),
                UserColumn::make('recipient')
                    ->label('Recipient'),
                Tables\Columns\IconColumn::make('completed')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (TicketTransfer $record): string => TicketTransferResource::getUrl('view', ['record' => $record->id])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
            ]);
    }
}
