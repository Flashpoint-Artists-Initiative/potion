<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Event;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $view = 'filament.app.pages.dashboard';

    public ?Event $event = null;

    public bool $hasReservedTickets;

    public bool $hasPendingTransfers;

    public function getTitle(): string|Htmlable
    {
        return $this->event->appDashboardContent->title ?? 'Dashboard';
    }

    #[On('active-event-updated')]
    public function mount(): void
    {
        $this->event = Event::getCurrentEvent();
        $this->hasReservedTickets = Auth::authenticate()->reservedTickets()->currentEvent()->canBePurchased()->exists() &&
            Event::getCurrentEvent()?->endDateCarbon->isFuture();
        $this->hasPendingTransfers = Auth::authenticate()->receivedTicketTransfers()->pending()->exists();
    }
}
