<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Enums\LockdownEnum;
use App\Filament\App\Clusters\UserPages;
use App\Livewire\PurchasedTicketsTable;
use App\Livewire\ReservedTicketsTable;
use App\Models\Event;
use App\Models\User;
use App\Services\QRCodeService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class Tickets extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'My Tickets';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.clusters.user-pages.pages.tickets';

    protected static ?string $cluster = UserPages::class;

    public bool $hasMultipleTickets;

    public bool $ticketLockdown;

    public function ticketsInfolist(Infolist $infolist): Infolist
    {
        /** @var User */
        $user = Auth::user();

        return $infolist
            ->schema([
                Livewire::make(PurchasedTicketsTable::class)->key('purchased-tickets-table'),
                Livewire::make(ReservedTicketsTable::class)->key('reserved-tickets-table')
                    ->visible(fn () => $user->availableReservedTickets()->currentEvent()->exists() &&
                        Event::getCurrentEvent()?->endDateCarbon->isFuture()),
            ])
            ->state([
                'name' => 'John Doe',
                'email' => 'joe@example.com',
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qr')
                ->label('Your QR Code')
                ->icon('heroicon-o-qr-code')
                ->modalContent(view('filament.app.clusters.user-pages.modals.ticket-qr', [
                    'qrCode' => $this->getQrCode(),
                ]))
                ->modalCancelAction(false)
                ->modalSubmitActionLabel('Close')
                ->visible(function () {
                    if (Event::getCurrentEvent()?->endDateCarbon->isPast()) {
                        return false;
                    }

                    return Auth::user()?->getValidTicketForEvent(Event::getCurrentEventId()) ?? false;
                }),
        ];
    }

    protected function getQrCode(): string
    {
        /** @var QRCodeService */
        $qrCodeService = App::make(QRCodeService::class);
        $userId = (int) Auth::id();
        $eventId = Event::getCurrentEventId();

        $content = $qrCodeService->buildTicketContent($userId, $eventId);

        $qr = $qrCodeService->buildQrCode($content);

        return $qr ?? '';
    }

    public function ticketInfoAction(): Action
    {
        return Action::make('ticketInfo')
            ->link()
            ->size('large')
            ->label('Find out more about how ticketing works.')
            ->modalContent(view('filament.app.modals.ticket-info'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    #[On('active-event-updated')]
    public function mount(): void
    {
        $this->hasMultipleTickets = Auth::authenticate()->purchasedTickets()->currentEvent()->count() > 1;
        $this->ticketLockdown = LockdownEnum::Tickets->isLocked();
    }
}
