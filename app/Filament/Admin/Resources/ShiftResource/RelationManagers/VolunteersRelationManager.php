<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ShiftResource\RelationManagers;

use App\Filament\Admin\Resources\UserResource;
use App\Filament\Tables\Columns\UserColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Models\Volunteering\Shift;

class VolunteersRelationManager extends RelationManager
{
    protected static string $relationship = 'volunteers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                UserColumn::makeForUserModel(),
                    // ->iconColor($this->checkAccess('primary'))
                    // ->icon('heroicon-m-user')
                    // ->url($this->checkAccess(
                    //     function ($record) {
                    //         if ($record->{$this->getUserRelation()}) {
                    //             return UserResource::getUrl('view', ['record' => $record->{$this->getUserRelation()}->id]);
                    //         }

                    //         return null;
                    //     }
                    // )),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->color('primary')
                    ->label('Add Volunteer')
                    ->visible(fn () => Auth::authenticate()->can('shifts.attach'))
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Remove')
                    ->visible(fn () => Auth::authenticate()->can('shifts.detach')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
