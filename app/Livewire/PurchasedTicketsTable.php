<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\LockdownEnum;
use App\Filament\App\Clusters\UserPages\Pages\TicketTransfers;
use App\Models\Event;
use App\Models\Ticketing\PurchasedTicket;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;
use Livewire\Component;

class PurchasedTicketsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    // @phpstan-ignore-next-line Required by parent class
    protected $listeners = [
        'active-event-updated' => '$refresh',
    ];

    public function render(): string
    {
        return <<<'HTML'
        <div class="flex flex-col flex-1 gap-y-8 print-hide">
            <span class="text-3xl font-semibold">Your Purchased Tickets</span>
            {{  $this->table }}
        </div>
        HTML;
    }

    public function table(Table $table): Table
    {
        $currentEvent = Event::getCurrentEvent();
        $url = route('filament.app.pages.purchase');
        $buyButton = false;

        if (! $currentEvent) {
            $emptyDescription = 'No event is currently active.';
        } elseif ($currentEvent->endDateCarbon->isPast()) {
            $emptyDescription = "{$currentEvent->name} has ended.";
        } elseif ($currentEvent->finalTicketEndDate?->isPast() ?? true) {
            $emptyDescription = "There are no more tickets for {$currentEvent->name} available";
        } else {
            $emptyDescription = "You have not purchased any tickets for {$currentEvent->name}.";
            $buyButton = true;
        }

        return $table
            ->query(PurchasedTicket::query()->currentUser()->currentEvent()->noActiveTransfer())
            ->columns([
                TextColumn::make('ticketType.name')
                    ->label('Ticket Type')
                    ->formatStateUsing(function (PurchasedTicket $ticket) {
                        $output = $ticket->ticketType->name;
                        if ($ticket->ticketType->addon) {
                            $output .= ' ' . Blade::render('<x-filament::badge style="display: inline-flex;">Addon</x-filament::badge>');
                        }

                        return $output;
                    })
                    ->html()
                    ->description(fn (PurchasedTicket $ticket) => $ticket->reservedTicket?->note),
                TextColumn::make('created_at')
                    ->label('Purchase Date')
                    ->dateTime('F jS, Y g:i A T', 'America/New_York'),
                TextColumn::make('ticketType.price')
                    ->label('Price')
                    ->money('USD'),
            ])
            ->actions([
                TableAction::make('transfer')
                    ->label('Transfer')
                    ->color(Color::Blue)
                    ->url(fn (PurchasedTicket $ticket) => TicketTransfers::getUrl(['purchased' => $ticket->id, 'action' => 'newTransfer']))
                    ->visible(fn (PurchasedTicket $ticket) => $ticket->ticketType->transferable &&
                        $ticket->event->endDateCarbon->isFuture() &&
                        ! LockdownEnum::Tickets->isLocked()
                    ),
            ])
            ->paginated(false)
            ->emptyStateHeading('No tickets purchased')
            ->emptyStateDescription($emptyDescription)
            ->emptyStateActions([
                TableAction::make('purchase')
                    ->label('Purchase Tickets')
                    ->url($url)
                    ->visible($buyButton),
            ]);
    }
}
