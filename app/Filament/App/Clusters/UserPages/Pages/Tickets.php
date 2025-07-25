<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters\UserPages\Pages;

use App\Enums\LockdownEnum;
use App\Filament\App\Clusters\UserPages;
use App\Forms\Components\WaiverSignatureInput;
use App\Livewire\PurchasedTicketsTable;
use App\Livewire\ReservedTicketsTable;
use App\Models\Event;
use App\Models\User;
use App\Services\QRCodeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class Tickets extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'My Tickets';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.clusters.user-pages.pages.tickets';

    protected static ?string $cluster = UserPages::class;

    public bool $hasMultipleTickets;

    public bool $hasTickets;

    public bool $ticketLockdown;

    public bool $showWaiverWarning;

    public bool $showCellServiceWarning;

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

                    if ($this->showWaiverWarning) {
                        return false;
                    }

                    return Auth::user()?->getValidTicketsForEvent(Event::getCurrentEventId())->count() > 0;
                }),
        ];
    }

    protected function getQrCode(): string
    {
        /** @var QRCodeService */
        $qrCodeService = App::make(QRCodeService::class);
        $user = Auth::user();
        $event = Event::getCurrentEvent();

        if (! $event || ! $user) {
            throw new \RuntimeException('No current event found.');
        }

        $content = $qrCodeService->buildTicketContent($user->id, $event->id);

        $qr = $qrCodeService->buildQrCode($content, $event->name, $user->email);

        return $qr ?? '';
    }

    public function ticketInfoAction(): Action
    {
        return Action::make('ticketInfo')
            ->link()
            ->size('large')
            ->label('Find out more about how ticketing works.')
            ->modalHeading('How Ticketing Works')
            ->modalContent(view('filament.app.modals.ticket-info'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    #[On('active-event-updated')]
    public function mount(): void
    {
        $count = Auth::authenticate()->getValidTicketsForEvent()->count();
        $this->hasTickets = $count > 0;
        $this->hasMultipleTickets = $count > 1;
        $this->ticketLockdown = LockdownEnum::Tickets->isLocked();

        $waiver = Event::getCurrentEvent()?->waiver;
        $hasSignedWaiver = (Auth::user()?->hasSignedWaiverForEvent(Event::getCurrentEventId()) ?? false);

        $eventIsFuture = Event::getCurrentEvent()?->endDateCarbon->isFuture();

        $this->showWaiverWarning = $waiver && $this->hasTickets && ! $hasSignedWaiver;
        $this->showCellServiceWarning = $this->hasTickets && $eventIsFuture;
    }

    public function signWaiverAction(): Action
    {
        $waiver = Event::getCurrentEvent()?->waiver;

        return Action::make('signWaiver')
            ->label('sign a waiver')
            ->link()
            ->size('')
            ->action(fn (array $data) => $this->createCompletedWaiver($data))
            ->modalHeading('Sign Waiver')
            ->modalWidth(MaxWidth::FiveExtraLarge)
            ->form([
                Placeholder::make('title')
                    ->content(new HtmlString('<h1 class="text-2xl">' . ($waiver->title ?? '') . '</h1>'))
                    ->label('')
                    ->dehydrated(false),
                Placeholder::make('waiver')
                    ->content(new HtmlString($waiver->content ?? ''))
                    ->label('')
                    ->dehydrated(false),
                WaiverSignatureInput::make('signature'),
            ]);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function createCompletedWaiver(array $data): void
    {
        if ($waiver = Event::getCurrentEvent()?->waiver) {
            $waiver->completedWaivers()->create([
                'user_id' => Auth::id(),
                'form_data' => [
                    'signature' => $data['signature'],
                ],
            ]);

            $this->showWaiverWarning = false;
        }
    }
}
