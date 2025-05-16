<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BanResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Gerenuk\FilamentBanhammer\Resources\BanhammerResource;

class BanResource extends BanhammerResource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->required(fn ($operation) => $operation == 'create')
                    ->hintAction(
                        Actions\Action::make('findUser')
                            ->label('Search users by name or email')
                            ->icon('heroicon-o-magnifying-glass')
                            ->form([
                                Forms\Components\Select::make('userId')
                                    ->getSearchResultsUsing(
                                        fn (string $query) => User::query()
                                            ->selectRaw('id, CONCAT(legal_name, " (", email, ")") as concat_name')
                                            ->where('email', 'like', "%{$query}%")
                                            ->orWhere('display_name', 'like', "%{$query}%")
                                            ->orWhere('legal_name', 'like', "%{$query}%")
                                            ->limit(20)
                                            ->pluck('concat_name', 'id')
                                            ->toArray()
                                    )
                                    ->searchable(),
                            ])
                            ->action(function (array $data, Set $set) {
                                $set('email', User::findOrFail((int) $data['userId'])->email);
                            })
                            ->hidden(fn ($operation) => $operation !== 'create'),
                    ),
                Forms\Components\DatePicker::make('expired_at')
                    ->label('Expiration Date')
                    ->helperText('Leave blank for permanent ban'),
                Forms\Components\TextInput::make('comment')
                    ->label('Comment')
                    ->maxLength(255)
                    ->required()
                    ->helperText('Reason for the ban, not visible to the user'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBans::route('/'),
            'create' => Pages\CreateBan::route('/create'),
        ];
    }
}
