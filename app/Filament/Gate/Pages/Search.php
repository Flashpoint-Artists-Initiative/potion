<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Models\Event;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
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

    /** @var array<int,array{entry:string,id:int,timestamp:string}> */
    public array $searchHistory = [];

    public function boot(): void
    {
        $this->searchHistory = Cache::get('gateSearchHistory', []);
    }

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
                        $user = User::findOrFail($state);

                        $data = [
                            'entry' => $user->legal_name . ' (' . $user->email . ')',
                            'id' => $user->id,
                            'timestamp' => now()->toDateTimeString(),
                        ];

                        // Remove any matching entries so we don't have duplicates
                        $this->searchHistory = array_filter($this->searchHistory, function($entry) use ($user) {
                            return $entry['id'] !== $user->id;
                        });

                        // Add to the front of the array and limit to 10 entries
                        array_unshift($this->searchHistory, $data);
                        $this->searchHistory = array_slice($this->searchHistory, 0, 10);

                        Cache::put('gateSearchHistory', $this->searchHistory);

                        $this->redirect(Checkin::getUrl([
                            'userId' => $state,
                            'eventId' => Event::getCurrentEventId(),
                        ]));
                    })
                    ->statePath('userId')
                    ->autofocus(),
            ]);
    }

    public function searchHistoryInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state(['searchHistory' => $this->searchHistory])
            ->schema([
                RepeatableEntry::make('searchHistory')
                    ->label('Recent Searches')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('entry')
                            ->label('')
                            ->icon('heroicon-o-user')
                            ->url(function ($component): string {
                                $index = explode('.', $component->getStatePath())[1];
                                $data = $component
                                    ->getContainer()
                                    ->getParentComponent()
                                    ->getState()[$index];
                                
                                return Checkin::getUrl([
                                    'userId' => $data['id'],
                                    'eventId' => Event::getCurrentEventId(),
                                ]);
                            })
                            ->color('primary'),
                        TextEntry::make('timestamp')
                            ->label('')
                            ->icon('heroicon-o-clock')
                            ->color('secondary')
                            ->dateTime('D, n/j g:i A T', 'America/New_York'),   
                    ])
            ]);
    }
}
