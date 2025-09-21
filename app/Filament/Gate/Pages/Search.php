<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Models\Event;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * @property Form $form
 */
class Search extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string $view = 'filament.gate.pages.search';

    protected static ?int $navigationSort = 2;

    public ?int $userId;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('search')
                ->label('Scan QR Code')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->url(Dashboard::getUrl()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Select Attendee')
                    ->searchable()
                    ->live()
                    ->required()
                    ->helperText(fn($state) => $state)
                    ->getSearchResultsUsing(function (string $search) {
                        return User::where('legal_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->select(DB::raw("id, CONCAT(legal_name, ' (', email, ')') AS legal_name"))
                        ->limit(50)
                        ->pluck('legal_name', 'id')
                        ->toArray();
                    })
                    ->afterStateUpdated(function(int $state) {
                        $this->redirect(Checkin::getUrl([
                            'userId' => $state,
                            'eventId' => Event::getCurrentEventId(),
                        ]));
                    })
                    ->statePath('userId')
                    ->autofocus(),
            ]);
    }
}
