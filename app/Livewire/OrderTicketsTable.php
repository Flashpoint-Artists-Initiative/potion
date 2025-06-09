<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Admin\Resources\PurchasedTicketResource\Pages\ViewPurchasedTicket;
use App\Filament\Tables\Columns\UserColumn;
use App\Models\Ticketing\Order;
use App\Models\Ticketing\PurchasedTicket;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class OrderTicketsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public Order $record;

    public bool $admin = false;

    public function render(): string
    {
        return <<<'HTML'
        <div class="grid flex-1 auto-cols-fr gap-y-2">
            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Items</span>
            {{  $this->table }}
        </div>
        HTML;
    }

    public function table(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->query(PurchasedTicket::query()->where('order_id', $this->record->id)->withTrashed())
            ->columns([
                TextColumn::make('id')
                    ->label('Ticket Number')
                    ->prefix('#')
                    ->url(fn (PurchasedTicket $record) => $this->admin ? ViewPurchasedTicket::getUrl(['record' => $record->id]) : null)
                    ->color(fn () => $this->admin ? 'primary' : null)
                    ->weight(fn () => $this->admin ? 'bold' : null),
                TextColumn::make('ticketType.name')
                    ->description(fn (PurchasedTicket $record) => $record->reservedTicket?->note),
                TextColumn::make('ticketType.price')
                    ->label('Price')
                    ->money('USD'),
                UserColumn::make('user')
                    ->label('Current Owner')
                    ->visible($this->admin),
            ])
            ->paginated(false);
    }
}
